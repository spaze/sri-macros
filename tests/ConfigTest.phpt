<?php

/**
 * Test: Spaze\SubresourceIntegrity\Config.
 *
 * @testCase Spaze\SubresourceIntegrity\ConfigTest
 * @author Michal Špaček
 * @package Spaze\SubresourceIntegrity\Config
 */

use Spaze\SubresourceIntegrity\Config;
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';

class ConfigTest extends Tester\TestCase
{

	public function testGetUrl()
	{
		$config = new Config();
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
		$config = new Config();
		$config->setHashingAlgos('sha256');
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo' => '/foo.js']);
		Assert::same('sha256-VW3caaddC+Dsr8gs1GV2ZsgGPxPXYiggWcOf9dvxgRY=', $config->getHash('foo'));
	}


	public function testGetMultipleHashes()
	{
		$config = new Config();
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
		$config = new Config();
		$config->setResources(['foo' => ['url' => 'pluto://goofy']]);
		Assert::same('pluto://goofy', $config->getUrl('foo'));
	}


	public function testGetRemoteHash()
	{
		$config = new Config();
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
		$config = new Config();
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

}

$testCase = new ConfigTest();
$testCase->run();
