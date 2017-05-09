<?php

/**
 * Test: Spaze\SubresourceIntegrity\Config.
 *
 * @testCase Spaze\SubresourceIntegrity\ConfigTest
 * @author Michal Špaček
 * @package Spaze\SubresourceIntegrity\Config
 */

use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\Exceptions;
use Spaze\SubresourceIntegrity\FileBuilder;
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';

class ConfigTest extends Tester\TestCase
{

	public $tempDir;


	private function getConfig()
	{
		$config = new Config(new FileBuilder());
		return $config;
	}


	public function testGetUrl()
	{
		$config = $this->getConfig();
		$config->setLocalPrefix([
			'url' => '/chuck/norris/',
		]);
		$config->setResources([
			'foo' => [
				'url' => 'https://bar'
			],
			'bar' => '/waldo/quux.js',
		]);

		Assert::same('https://bar', $config->getUrl('foo'));
		Assert::same('/chuck/norris/waldo/quux.js', $config->getUrl('bar'));
	}


	public function testGetHash()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo' => '/foo.js']);
		Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
	}


	public function testGetMultipleHashes()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256', 'sha512']);
		$config->setLocalPrefix(['path' => 'foo/../']);
		$config->setResources(['foo' => 'foo.js']);
		$hashes = [
			'sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=',
			'sha512-Ya/YX2JNDJkMuwnP7Og+2HpkYVwwBaUXRk1pRbyp6kYhnlTNKnTlVGi/KLsGnZETuK+TBHhA4itCCy1/74UTvg=='
		];
		Assert::same(implode(' ', $hashes), $config->getHash('foo'));
	}


	public function testGetRemoteUrl()
	{
		$config = $this->getConfig();
		$config->setResources(['foo' => ['url' => 'pluto://goofy']]);
		Assert::same('pluto://goofy', $config->getUrl('foo'));
	}


	public function testGetRemoteHash()
	{
		$config = $this->getConfig();
		$config->setResources([
			'foo' => [
				'url' => 'pluto://goofy',
				'hash' => 'sha123-pluto'
			],
		]);
		Assert::same('pluto://goofy', $config->getUrl('foo'));
		Assert::same('sha123-pluto', $config->getHash('foo'));
	}


	public function testGetMultipleRemoteHashes()
	{
		$config = $this->getConfig();
		$config->setResources([
			'foo' => [
				'url' => 'pluto://goofy',
				'hash' => [
					'sha123-pluto',
					'sha246-goofy',
				],
			],
		]);
		Assert::same('pluto://goofy', $config->getUrl('foo'));
		Assert::same('sha123-pluto sha246-goofy', $config->getHash('foo'));
	}


	public function testUnknownLocalMode()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('direct');
		Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
		$config->setLocalMode('foo');
		Assert::exception(function() use ($config) {
			Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
		}, Exceptions\UnknownModeException::class);
	}


	public function testBuildLocalMode()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('build');
		Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
		Assert::true(file_exists($this->tempDir . '/VW3caaddC-Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY.js'));
	}


	public function testBuildLocalModeNonExistingDir()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests/does/not/exist']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('build');
		Assert::exception(function() use ($config) {
			Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
		}, Exceptions\DirectoryNotWritableException::class);
	}


	public function testDirectLocalModePlusSign()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo+bar' => '/foo.js']);
		$config->setLocalMode('direct');
		Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo+bar'));
	}


	public function testBuildLocalModePlusSign()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setResources(['foo' => '/foo.js', 'waldo' => '/waldo.js']);
		$config->setLocalMode('build');
		Assert::same('sha256-JVfM7rc8NwU7pFP1p7p4YJBW++QdyCbSnjq7AO4UIVg=', $config->getHash('foo+waldo'));
		Assert::true(file_exists($this->tempDir . '/JVfM7rc8NwU7pFP1p7p4YJBW--QdyCbSnjq7AO4UIVg.js'));
	}

}

$testCase = new ConfigTest();
$testCase->tempDir = __DIR__ . '/../temp/tests';
Tester\Helpers::purge($testCase->tempDir);
$testCase->run();
