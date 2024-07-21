<?php
/** @noinspection PhpUnhandledExceptionInspection */
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


	public function testExtension(): void
	{
		$localPrefixDir = __DIR__ . '/../../../temp';
		$testsDir = 'tests';
		$buildDir = "{$testsDir}/" . getenv(Environment::VariableThread);
		$tempDir = "{$localPrefixDir}/{$buildDir}";
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
		$config->setLocalPrefix((object)['path' => $localPrefixDir, 'build' => $buildDir]);

		$engine = new Engine();
		$engine->setTempDirectory($tempDir);
		$engine->addExtension(new LatteExtension($config));
		$domQuery = DomQuery::fromHtml($engine->renderToString(__DIR__ . '/template.latte'));

		$expectedFooHash = 'sha256-Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw= sha384-a7C/tKiJvZ9vnbfkuQL8yySB6ytEA+fXmUyO+YjOHmzk9Drl6DOZwoFwcqI1ciNN';
		$expectedBarHash = 'sha256-kMw4RzIBrprNkv5ZuQaK5ZQDTGoU7lsONAFHDf/9vt0= sha384-AB1C4RbQdi1s8zgzj5QouyFTB8/jk5x/Z9ykg97akPjODc4Fw/uo2vzLTJn/c6jZ';
		$expectedFooPlusBarHash = 'sha256-FLuwXqLdvgTg4YlCbmz6IBWX9u5uTTHxjkDdHohJPdE= sha384-SNXaST4kjWjXtU2UFrrc13GOpkkr0EMlJELJ5kjJuLEShmIR4QMe9d40kt7nnx1y';
		$expectedFooPluStringPlusBarHash = 'sha256-T+V23c36b9BEPbZ5a8UEUGzkpKoReczQ+eZ8Xlx8b9k= sha384-BSV21Wdvvy9B6XSdhSUaSLR0PN71n6ReAhYEIOQ340znPa9p3Ng+H6Q+hp7zBYpI';
		$expectedFooFilename = 'Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.js';
		$expectedBarFilename = 'kMw4RzIBrprNkv5ZuQaK5ZQDTGoU7lsONAFHDf_9vt0.js';
		$expectedFooPlusBarFilename = 'FLuwXqLdvgTg4YlCbmz6IBWX9u5uTTHxjkDdHohJPdE.js';

		$scripts = $domQuery->find('script');
		Assert::matchFile($localPrefixDir . $scripts[0]['src'], 'FOO');
		Assert::same($expectedFooHash, (string)$scripts[0]['integrity']);
		Assert::matchFile($localPrefixDir . $scripts[1]['src'], 'WALDO');
		Assert::same($expectedBarHash, (string)$scripts[1]['integrity']);
		Assert::matchFile($localPrefixDir . $scripts[2]['src'], "FOO\nWALDO");
		Assert::same($expectedFooPlusBarHash, (string)$scripts[2]['integrity']);
		Assert::matchFile($localPrefixDir . $scripts[3]['src'], "FOO\nwaldo + fred+baz WALDO");
		Assert::same($expectedFooPluStringPlusBarHash, (string)$scripts[3]['integrity']);

		// Starting with Latte 3.0.17, unquoted strings can contain +, so these behave differently
		if (Engine::VersionId >= 30017) {
			Assert::matchFile($localPrefixDir . $scripts[4]['src'], 'BAR');
			Assert::matchFile($localPrefixDir . $scripts[5]['src'], 'BAR');
			Assert::matchFile($localPrefixDir . $scripts[6]['src'], 'BAR');
		} else {
			Assert::matchFile($localPrefixDir . $scripts[4]['src'], "WALDO\nbaz");
			Assert::matchFile($localPrefixDir . $scripts[5]['src'], "WALDO\nbaz");
			Assert::matchFile($localPrefixDir . $scripts[6]['src'], "WALDO\nbaz");
		}
		Assert::matchFile($localPrefixDir . $scripts[7]['src'], 'FRED');

		$styles = $domQuery->find('link[rel=stylesheet]');
		Assert::matchFile($localPrefixDir . $styles[0]['href'], 'FOO');
		Assert::same($expectedFooHash, (string)$styles[0]['integrity']);
		Assert::matchFile($localPrefixDir . $styles[1]['href'], 'WALDO');
		Assert::same($expectedBarHash, (string)$styles[1]['integrity']);

		$urls = $domQuery->find('i[class=url]');
		Assert::same($expectedFooFilename, basename((string)$urls[0]));
		Assert::same($expectedBarFilename, basename((string)$urls[1]));
		Assert::same($expectedFooPlusBarFilename, basename((string)$urls[2]));

		$hashes = $domQuery->find('i[class=hash]');
		Assert::same($expectedFooHash, (string)$hashes[0]);
		Assert::same($expectedBarHash, (string)$hashes[1]);
		Assert::same($expectedFooPlusBarHash, (string)$hashes[2]);
	}

}

(new LatteExtensionTest())->run();
