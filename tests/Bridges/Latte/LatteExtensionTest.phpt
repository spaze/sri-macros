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
			'bar' => "{$assetsDir}/bar.js",
			'bar + baz' => "{$assetsDir}/barPlusBaz.js",
		]);
		$config->setHashingAlgos(['sha256', 'sha384']);
		$config->setLocalPrefix('', $this->localPrefixDir, $buildDir);

		$engine = new Engine();
		$engine->setTempDirectory($tempDir);
		$engine->addExtension(new LatteExtension($config));
		$domQuery = DomQuery::fromHtml($engine->renderToString(__DIR__ . '/template.latte'));

		$expectedFooHash = 'sha256-Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw= sha384-a7C/tKiJvZ9vnbfkuQL8yySB6ytEA+fXmUyO+YjOHmzk9Drl6DOZwoFwcqI1ciNN';
		$expectedBarHash = 'sha256-5inLrhrLKWwTh5XzgUmj78DriU4EHy3FiIZMgQO8WEM= sha384-J1hhHBD3aRAn2utMGavM/iAdZ/eWsV/kCzO/SB93/ZiRLqN8Qju+LVa7fwmR79NZ';
		$expectedFooPlusBarHash = 'sha256-Ih5GS88mg709j4LejYE+JfTLrw1p5wGcZIQppToB5/w= sha384-gQLDPOROfXXWmE0ES2guWRLXYC3NNYTijUj/sayJUyYBu1dOhQAmV5KHfFgRRHq8';
		$expectedFooPlusBarStringHash = 'sha256-BYC37kOqZK0x5tOcEzlNcYvCz27fpxNjn1cCjhgJoIw= sha384-hWnwC2svw1G8NHQj38MNY6MN7pqi0BwV5HlLAiSjKCnwFoN46VYtu1EQR8jmaFm2';
		$expectedFooPlusStringPlusBarHash = 'sha256-tliQaX/Jzn9phl61UIbyeB9+uk+7tYIxe9vkPW7ou7k= sha384-a/3u1+3mk9SxJy6pSFvMXTsAwIkYoKMK9S05Nxn0yvuzF09mTola03kA3y1ymLCz';
		$expectedBarPlusStringHash = 'sha256-vlwQQ1h7l3jhTXyQnyd2pfxXoVcS+eqdZ1D07P8aiGc= sha384-BJ5jj1klAHHCBkZW4CZEclwJdWLQ6xVqqmtM6nx2xZkbDpwKWCiXWiPp1MzM0RUE';
		$expectedBarPlusBazWithSpacesHash = 'sha256-T28q4m7IfWZ8UrAAl8hT3bs+DKPBsaNCwsSkkPo+W9c= sha384-Azyjiz7SE2KzCLhilcoWDRKtLMUoq6VPQoQqFhCMkqQ1MwMbyKcQvDo4dak61JYB';
		$expectedFooFilename = 'Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.js';
		$expectedBarFilename = '5inLrhrLKWwTh5XzgUmj78DriU4EHy3FiIZMgQO8WEM.js';
		$expectedFooPlusBarFilename = 'Ih5GS88mg709j4LejYE-JfTLrw1p5wGcZIQppToB5_w.js';

		$scripts = $domQuery->find('script');
		$this->assertFile($scripts[0], 'src', 'FOO', $expectedFooHash);
		$this->assertFile($scripts[1], 'src', 'BAR', $expectedBarHash);
		$this->assertFile($scripts[2], 'src', "FOO\nBAR", $expectedFooPlusBarHash);
		$this->assertFile($scripts[3], 'src', "FOO\nBAR", $expectedFooPlusBarHash);
		$this->assertFile($scripts[4], 'src', "foo + bar", $expectedFooPlusBarStringHash);
		$this->assertFile($scripts[5], 'src', "FOO\nBAR", $expectedFooPlusBarHash);
		$this->assertFile($scripts[6], 'src', "FOO\nwaldo + fred+baz BAR", $expectedFooPlusStringPlusBarHash);
		$this->assertFile($scripts[7], 'src', "BAR\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[8], 'src', "BAR\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[9], 'src', "BAR\nbaz", $expectedBarPlusStringHash);
		$this->assertFile($scripts[10], 'src', 'BAR+BAZ', $expectedBarPlusBazWithSpacesHash);

		$styles = $domQuery->find('link[rel=stylesheet]');
		$this->assertFile($styles[0], 'href', 'FOO', $expectedFooHash);
		$this->assertFile($styles[1], 'href', 'BAR', $expectedBarHash);

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
