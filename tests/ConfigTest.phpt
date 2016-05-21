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

}

$testCase = new ConfigTest();
$testCase->run();
