<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection SpellCheckingInspection Many Base64 strings in here */
declare(strict_types = 1);

namespace Spaze\tests\Bridges\Latte;

use Latte\Engine;
use Spaze\SubresourceIntegrity\Bridges\Latte\LatteExtension;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\FileBuilder;
use Spaze\SubresourceIntegrity\LocalMode;
use Tester\Assert;
use Tester\DomQuery;
use Tester\Environment;
use Tester\Helpers;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class LatteExtensionTest extends TestCase
{

	private string $localPrefixDir = __DIR__ . '/../../../temp';


	public function testExtension(): void
	{
		$testsDir = 'tests';
		$buildDir = "{$testsDir}/" . getenv(Environment::VariableThread);
		$tempDir = "$this->localPrefixDir/{$buildDir}";
		$assetsDir = "../{$testsDir}/assets";
		Helpers::purge($tempDir);

		$config = new Config(new FileBuilder());
		$config->setLocalMode(LocalMode::Build);
		$config->setResources([
			'foo' => "{$assetsDir}/foo.js",
			'bar' => "{$assetsDir}/waldo.js",
			'bar+baz' => "{$assetsDir}/bar.js",
			'bar + baz' => "{$assetsDir}/fred.js",
		]);
		$config->setHashingAlgos(['sha256', 'sha384']);
		$config->setLocalPrefix((object)['path' => $this->localPrefixDir, 'build' => $buildDir]);

		$engine = new Engine();
		$engine->setTempDirectory($tempDir);
		$engine->addExtension(new LatteExtension($config));
		$domQuery = DomQuery::fromHtml($engine->renderToString(__DIR__ . '/template.latte'));

		$expectedFooHash = 'sha256-Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw= sha384-a7C/tKiJvZ9vnbfkuQL8yySB6ytEA+fXmUyO+YjOHmzk9Drl6DOZwoFwcqI1ciNN';
		$expectedBarHash = 'sha256-kMw4RzIBrprNkv5ZuQaK5ZQDTGoU7lsONAFHDf/9vt0= sha384-AB1C4RbQdi1s8zgzj5QouyFTB8/jk5x/Z9ykg97akPjODc4Fw/uo2vzLTJn/c6jZ';
		$expectedFooPlusBarHash = 'sha256-FLuwXqLdvgTg4YlCbmz6IBWX9u5uTTHxjkDdHohJPdE= sha384-SNXaST4kjWjXtU2UFrrc13GOpkkr0EMlJELJ5kjJuLEShmIR4QMe9d40kt7nnx1y';
		$expectedFooPluStringPlusBarHash = 'sha256-T+V23c36b9BEPbZ5a8UEUGzkpKoReczQ+eZ8Xlx8b9k= sha384-BSV21Wdvvy9B6XSdhSUaSLR0PN71n6ReAhYEIOQ340znPa9p3Ng+H6Q+hp7zBYpI';
		$expectedBarPlusBazHash = 'sha256-5inLrhrLKWwTh5XzgUmj78DriU4EHy3FiIZMgQO8WEM= sha384-J1hhHBD3aRAn2utMGavM/iAdZ/eWsV/kCzO/SB93/ZiRLqN8Qju+LVa7fwmR79NZ';
		$expectedBarPlusStringHash = 'sha256-ZbqUMaVnHiiwZfSYaG0hEv+heAh0tdQXD9R2QVziTdI= sha384-r3D44oL4w8Z+3P5ZkCRdftwo84wIwpb5cC5kKrPOeI8G5/Py5yA6ySFEUTGi8Au8';
		$expectedBarPlusBazWithSpacesHash = 'sha256-HWXAW2XdmETpO0XxTwL+/ByGtYLKkEOYHvlMPcT4EfI= sha384-9/ckk6F1pQAc5Ql8t+lgdE2BAMydQcuP41bbfn7b67bVf6DozoQvOZe9/WM0jzWQ';
		$expectedFooFilename = 'Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.js';
		$expectedBarFilename = 'kMw4RzIBrprNkv5ZuQaK5ZQDTGoU7lsONAFHDf_9vt0.js';
		$expectedFooPlusBarFilename = 'FLuwXqLdvgTg4YlCbmz6IBWX9u5uTTHxjkDdHohJPdE.js';

		$scripts = $domQuery->find('script');
		$this->assertFile($scripts[0], 'src', 'FOO', $expectedFooHash);
		$this->assertFile($scripts[1], 'src', 'WALDO', $expectedBarHash);
		$this->assertFile($scripts[2], 'src', "FOO\nWALDO", $expectedFooPlusBarHash);
		$this->assertFile($scripts[3], 'src', "FOO\nwaldo + fred+baz WALDO", $expectedFooPluStringPlusBarHash);
		$this->assertFile($scripts[4], 'src', "WALDO\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[5], 'src', "WALDO\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[6], 'src', "WALDO\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[7], 'src', 'FRED', $expectedBarPlusBazWithSpacesHash);

		$styles = $domQuery->find('link[rel=stylesheet]');
		$this->assertFile($styles[0], 'href', 'FOO', $expectedFooHash);
		$this->assertFile($styles[1], 'href', 'WALDO', $expectedBarHash);

		$urls = $domQuery->find('i[class=url]');
		Assert::same($expectedFooFilename, basename((string)$urls[0]));
		Assert::same($expectedBarFilename, basename((string)$urls[1]));
		Assert::same($expectedFooPlusBarFilename, basename((string)$urls[2]));

		$hashes = $domQuery->find('i[class=hash]');
		Assert::same($expectedFooHash, (string)$hashes[0]);
		Assert::same($expectedBarHash, (string)$hashes[1]);
		Assert::same($expectedFooPlusBarHash, (string)$hashes[2]);
	}


	private function assertFile(DomQuery $element, string $attribute, string $expectedFileContents, string $expectedHash): void
	{
		Assert::matchFile($this->localPrefixDir . $element[$attribute], $expectedFileContents);
		Assert::same($expectedHash, (string)$element['integrity']);
		Assert::hasKey('empty-attribute', iterator_to_array($element->attributes()));
		Assert::same('', (string)$element['empty-attribute']);
		Assert::same('value', (string)$element['attribute']);
	}

}

(new LatteExtensionTest())->run();
