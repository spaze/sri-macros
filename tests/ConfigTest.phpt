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

	private $tempDir;

	private $config;


	public function setUp()
	{
		$this->config = new Config(new FileBuilder());
		$this->tempDir = __DIR__ . '/../temp/tests';
		Tester\Helpers::purge($this->tempDir);
	}


	public function testGetUrl()
	{
		$this->config->setLocalPrefix([
			'url' => '/chuck/norris/',
		]);
		$this->config->setResources([
			'foo' => [
				'url' => 'https://bar'
			],
			'bar' => '/waldo/quux.js',
		]);

		Assert::same('https://bar', $this->config->getUrl('foo'));
		Assert::same('https://bar', $this->config->getUrl('foo', 'ext'));
		Assert::same('/chuck/norris/waldo/quux.js', $this->config->getUrl('bar'));
		Assert::same('/chuck/norris/waldo/quux.js', $this->config->getUrl('bar', 'ext'));
	}


	public function testGetHash()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.']);
		$this->config->setResources(['foo' => '/foo.js']);
		Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', 'ext'));
	}


	public function testGetMultipleHashes()
	{
		$this->config->setHashingAlgos(['sha256', 'sha512']);
		$this->config->setLocalPrefix(['path' => 'foo/../']);
		$this->config->setResources(['foo' => 'foo.js']);
		$hashes = [
			self::HASH_FOO,
			'sha512-zAaAjLvuBRAzGql5dBMujcKWrreVviKdBkuueEsKh6XPQoHYLoyZJxt12yFI8IoCbBpg7Zyr24ysbSQkLaxAYw=='
		];
		Assert::same(implode(' ', $hashes), $this->config->getHash('foo'));
	}


	public function testGetRemoteUrl()
	{
		$this->config->setResources(['foo' => ['url' => 'pluto://goofy']]);
		Assert::same('pluto://goofy', $this->config->getUrl('foo'));
		Assert::same('pluto://goofy', $this->config->getUrl('foo', 'ext'));
	}


	public function testGetRemoteHash()
	{
		$this->config->setResources([
			'foo' => [
				'url' => 'pluto://goofy',
				'hash' => 'sha123-pluto'
			],
		]);
		Assert::same('pluto://goofy', $this->config->getUrl('foo'));
		Assert::same('pluto://goofy', $this->config->getUrl('foo', 'ext'));
		Assert::same('sha123-pluto', $this->config->getHash('foo'));
		Assert::same('sha123-pluto', $this->config->getHash('foo', 'ext'));
	}


	public function testGetMultipleRemoteHashes()
	{
		$this->config->setResources([
			'foo' => [
				'url' => 'pluto://goofy',
				'hash' => [
					'sha123-pluto',
					'sha246-goofy',
				],
			],
		]);
		Assert::same('pluto://goofy', $this->config->getUrl('foo'));
		Assert::same('sha123-pluto sha246-goofy', $this->config->getHash('foo'));
	}


	public function testUnknownLocalMode()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.']);
		$this->config->setResources(['foo' => '/foo.js']);
		$this->config->setLocalMode('direct');
		Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		$this->config->setLocalMode('foo');
		Assert::exception(function() {
			Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		}, Exceptions\UnknownModeException::class);
	}


	public function testBuildLocalMode()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$this->config->setResources(['foo' => '/foo.js']);
		$this->config->setLocalMode('build');
		Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		Assert::true(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.js'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', 'ignoredExt'));
		Assert::false(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ignoredExt'));
	}


	public function testBuildLocalModeExtension()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$this->config->setResources(['foo' => '/foo.js']);
		$this->config->setLocalMode('build');
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', 'ext'));
		Assert::true(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ext'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', 'ignoredExt'));
		Assert::false(file_exists($this->tempDir . '/fYZelZskZpGMmGOvypQtD7idfJrAyZuvw3SVBN7ZdzA.ignoredExt'));
	}


	public function testBuildLocalModeNonExistingDir()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests/does/not/exist']);
		$this->config->setResources(['foo' => '/foo.js']);
		$this->config->setLocalMode('build');
		Assert::exception(function() {
			Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		}, Exceptions\DirectoryNotWritableException::class);
	}


	public function testDirectLocalModePlusSign()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.']);
		$this->config->setResources(['foo+bar' => '/foo.js']);
		$this->config->setLocalMode('direct');
		Assert::same(self::HASH_FOO, $this->config->getHash('foo+bar'));
	}


	public function testBuildLocalModePlusSign()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$this->config->setResources(['foo' => '/foo.js', 'waldo' => '/waldo.js']);
		$this->config->setLocalMode('build');
		Assert::same('sha256-OKCqUCrz1KH7Or6Bh+kcYTB8fsSEsZxnHyaBFR1CVVw=', $this->config->getHash("foo+'baz'+waldo"));
		Assert::true(file_exists($this->tempDir . '/OKCqUCrz1KH7Or6Bh-kcYTB8fsSEsZxnHyaBFR1CVVw.js'));
	}


	public function testBuildLocalModeStringResourceOnly()
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix(['path' => '.', 'build' => '../temp/tests']);
		$this->config->setLocalMode('build');
		Assert::exception(function() {
			$this->config->getHash('"foobar"');
		}, Exceptions\UnknownExtensionException::class);
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $this->config->getHash('"foobar"', 'js'));
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $this->config->getHash('"foo"+"bar"', 'ext'));
		Assert::same('/../temp/tests/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.js', $this->config->getUrl('"foobar"', 'nowIgnored'));
		Assert::same('/../temp/tests/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.ext', $this->config->getUrl('"foo"+"bar"', 'nowIgnored'));
	}

}

(new ConfigTest())->run();
