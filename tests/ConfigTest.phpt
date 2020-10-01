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

require __DIR__ . '/bootstrap.php';

class ConfigTest extends Tester\TestCase
{

	private const HASH_FOO = 'sha256-fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA=';

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
		Assert::same('https://bar', $config->getUrl('foo', 'ext'));
		Assert::same('/chuck/norris/waldo/quux.js', $config->getUrl('bar'));
		Assert::same('/chuck/norris/waldo/quux.js', $config->getUrl('bar', 'ext'));
	}


	public function testGetHash()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo' => '/foo.js']);
		Assert::same(self::HASH_FOO, $config->getHash('foo'));
		Assert::same(self::HASH_FOO, $config->getHash('foo', 'ext'));
	}


	public function testGetMultipleHashes()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256', 'sha512']);
		$config->setLocalPrefix(['path' => 'foo/../']);
		$config->setResources(['foo' => 'foo.js']);
		$hashes = [
			self::HASH_FOO,
			'sha512-zAaAjLvuBRAzGql5dBMujcKWrreVviKdBkuueEsKh6XPQoHYLoyZJxt12yFI8IoCbBpg7Zyr24ysbSQkLaxAYw=='
		];
		Assert::same(implode(' ', $hashes), $config->getHash('foo'));
	}


	public function testGetRemoteUrl()
	{
		$config = $this->getConfig();
		$config->setResources(['foo' => ['url' => 'pluto://goofy']]);
		Assert::same('pluto://goofy', $config->getUrl('foo'));
		Assert::same('pluto://goofy', $config->getUrl('foo', 'ext'));
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
		Assert::same('pluto://goofy', $config->getUrl('foo', 'ext'));
		Assert::same('sha123-pluto', $config->getHash('foo'));
		Assert::same('sha123-pluto', $config->getHash('foo', 'ext'));
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
		Assert::same(self::HASH_FOO, $config->getHash('foo'));
		$config->setLocalMode('foo');
		Assert::exception(function() use ($config) {
			Assert::same(self::HASH_FOO, $config->getHash('foo'));
		}, Exceptions\UnknownModeException::class);
	}


	public function testBuildLocalMode()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('build');
		Assert::same(self::HASH_FOO, $config->getHash('foo'));
		Assert::true(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.js'));
		Assert::same(self::HASH_FOO, $config->getHash('foo', 'ignoredExt'));
		Assert::false(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ignoredExt'));
	}


	public function testBuildLocalModeExtension()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('build');
		Assert::same(self::HASH_FOO, $config->getHash('foo', 'ext'));
		Assert::true(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ext'));
		Assert::same(self::HASH_FOO, $config->getHash('foo', 'ignoredExt'));
		Assert::false(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ignoredExt'));
	}


	public function testBuildLocalModeNonExistingDir()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests/does/not/exist']);
		$config->setResources(['foo' => '/foo.js']);
		$config->setLocalMode('build');
		Assert::exception(function() use ($config) {
			Assert::same(self::HASH_FOO, $config->getHash('foo'));
		}, Exceptions\DirectoryNotWritableException::class);
	}


	public function testDirectLocalModePlusSign()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.']);
		$config->setResources(['foo+bar' => '/foo.js']);
		$config->setLocalMode('direct');
		Assert::same(self::HASH_FOO, $config->getHash('foo+bar'));
	}


	public function testBuildLocalModePlusSign()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setResources(['foo' => '/foo.js', 'waldo' => '/waldo.js']);
		$config->setLocalMode('build');
		Assert::same('sha256-OKCqUCrz1KH7Or6Bh+kcYTB8fsSEsZxnHyaBFR1CVVw=', $config->getHash("foo+'baz'+waldo"));
		Assert::true(file_exists($this->tempDir . '/OKCqUCrz1KH7Or6Bh-kcYTB8fsSEsZxnHyaBFR1CVVw.js'));
	}


	public function testBuildLocalModeStringResourceOnly()
	{
		$config = $this->getConfig();
		$config->setHashingAlgos(['sha256']);
		$config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$config->setLocalMode('build');
		Assert::exception(function() use ($config) {
			$config->getHash('"foobar"');
		}, Exceptions\UnknownExtensionException::class);
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $config->getHash('"foobar"', 'js'));
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $config->getHash('"foo"+"bar"', 'ext'));
		Assert::same('/../temp/tests/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.js', $config->getUrl('"foobar"', 'nowIgnored'));
		Assert::same('/../temp/tests/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.ext', $config->getUrl('"foo"+"bar"', 'nowIgnored'));
	}

}

$testCase = new ConfigTest();
$testCase->tempDir = __DIR__ . '/../temp/tests';
Tester\Helpers::purge($testCase->tempDir);
$testCase->run();
