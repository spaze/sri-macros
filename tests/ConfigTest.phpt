<?php
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection SpellCheckingInspection Many Base64 strings in here */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Exceptions\CannotGetFilePathForRemoteResourceException;
use Spaze\SubresourceIntegrity\Exceptions\DirectoryNotWritableException;
use Spaze\SubresourceIntegrity\Exceptions\UnknownExtensionException;
use Tester\Assert;
use Tester\Environment;
use Tester\Helpers;
use Tester\TestCase;
use ValueError;

require __DIR__ . '/bootstrap.php';

/** @testCase */
class ConfigTest extends TestCase
{

	private const HASH_FOO = 'sha256-Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw=';

	private string $tempDir;
	private string $buildDir;
	private Config $config;


	public function __construct()
	{
		$this->buildDir = '../temp/tests/' . getenv(Environment::VariableThread);
		$this->tempDir = __DIR__ . '/' . $this->buildDir;
		Helpers::purge($this->tempDir);
	}


	public function setUp(): void
	{
		$this->config = new Config(new FileBuilder());
		$this->config->setLocalPrefix((object)['path' => '.', 'build' => $this->buildDir]);
	}


	public function testGetUrl(): void
	{
		$this->config->setLocalPrefix((object)[
			'url' => '/chuck/norris/',
		]);
		$this->config->setResources([
			'foo' => [
				'url' => 'https://bar'
			],
			'bar' => '/waldo/quux.js',
		]);

		Assert::same('https://bar', $this->config->getUrl('foo'));
		Assert::same('https://bar', $this->config->getUrl('foo', HtmlElement::Link));
		Assert::same('/chuck/norris/waldo/quux.js', $this->config->getUrl('bar'));
		Assert::same('/chuck/norris/waldo/quux.js', $this->config->getUrl('bar', HtmlElement::Script));
	}


	public function testGetFileResource(): void
	{
		$this->config->setLocalPrefix((object)[
			'path' => '/chuck/norris/',
		]);
		$this->config->setResources([
			'foo' => [
				'url' => 'https://nope'
			],
			'bar' => '/waldo/quux.js',
		]);
		Assert::exception(function (): void {
			$this->config->getFileResource('foo');
		}, CannotGetFilePathForRemoteResourceException::class, 'Cannot get file path for remote resource foo');
		$fileResource = $this->config->getFileResource('bar');
		Assert::same('/chuck/norris/waldo/quux.js', $fileResource->getFilename());
		Assert::same('js', $fileResource->getExtension());
	}


	public function testGetHash(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setResources(['foo' => '/assets/foo.js']);
		Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', HtmlElement::Link));
	}


	public function testGetMultipleHashes(): void
	{
		$this->config->setHashingAlgos(['sha256', 'sha512']);
		$this->config->setLocalPrefix((object)['path' => 'foo/../assets']);
		$this->config->setResources(['foo' => 'foo.js']);
		$hashes = [
			self::HASH_FOO,
			'sha512-Mz6AdmkKsEt6DFZ+hhfQVKEoZZISIT97SmgJEwKAupO+tWVfhgGnb59VxH3W49/Gf/WfQIiZXHsAHMafjAtqyg=='
		];
		Assert::same(implode(' ', $hashes), $this->config->getHash('foo'));
	}


	public function testGetRemoteUrl(): void
	{
		$this->config->setResources(['foo' => ['url' => 'pluto://goofy']]);
		Assert::same('pluto://goofy', $this->config->getUrl('foo'));
		Assert::same('pluto://goofy', $this->config->getUrl('foo', HtmlElement::Link));
	}


	/**
	 * @throws \Spaze\SubresourceIntegrity\Exceptions\InvalidResourceAliasException Invalid character in resource alias, using + with remote files or in direct mode?
	 */
	public function testGetRemoteUrlInvalidCharacters(): void
	{
		$this->config->setResources(['foo+bar' => ['url' => 'pluto://goofy']]);
		$this->config->getUrl('foo+bar');
	}


	public function testGetRemoteHash(): void
	{
		$this->config->setResources([
			'foo' => [
				'url' => 'pluto://goofy',
				'hash' => 'sha123-pluto'
			],
		]);
		Assert::same('pluto://goofy', $this->config->getUrl('foo'));
		Assert::same('pluto://goofy', $this->config->getUrl('foo', HtmlElement::Link));
		Assert::same('sha123-pluto', $this->config->getHash('foo'));
		Assert::same('sha123-pluto', $this->config->getHash('foo', HtmlElement::Link));
	}


	/**
	 * @throws \Spaze\SubresourceIntegrity\Exceptions\InvalidResourceAliasException Invalid character in resource alias, using + with remote files or in direct mode?
	 */
	public function testGetRemoteHashInvalidCharacters(): void
	{
		$this->config->setResources([
			'foo+bar' => [
				'url' => 'pluto://goofy',
				'hash' => 'sha123-pluto'
			],
		]);
		$this->config->getHash('foo+bar');
	}


	public function testGetMultipleRemoteHashes(): void
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


	public function testBuildLocalMode(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setResources(['foo' => '/assets/foo.js']);
		$this->config->setLocalMode(LocalMode::Build);
		Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		Assert::true(file_exists($this->tempDir . '/Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.js'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', HtmlElement::Link));
		Assert::false(file_exists($this->tempDir . '/Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.ignoredExt'));
	}


	public function testBuildLocalModeExtension(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setResources(['foo' => '/assets/foo.js']);
		$this->config->setLocalMode(LocalMode::Build);
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', HtmlElement::Link));
		Assert::true(file_exists($this->tempDir . '/Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.css'));
		Assert::same(self::HASH_FOO, $this->config->getHash('foo', HtmlElement::Script));
		Assert::false(file_exists($this->tempDir . '/Fwa1wY5DWAQbRjmV78MPj3IXZvqw4BjVDYWXi0bfATw.ignoredExt'));
	}


	public function testBuildLocalModeNonExistingDir(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalPrefix((object)['build' => "{$this->buildDir}/does/not/exist"]);
		$this->config->setResources(['foo' => '/assets/foo.js']);
		$this->config->setLocalMode(LocalMode::Build);
		Assert::exception(function() {
			Assert::same(self::HASH_FOO, $this->config->getHash('foo'));
		}, DirectoryNotWritableException::class);
	}


	/**
	 * @throws \Spaze\SubresourceIntegrity\Exceptions\InvalidResourceAliasException Invalid character in resource alias, using + with remote files or in direct mode?
	 */
	public function testDirectLocalModePlusSign(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalMode(LocalMode::Direct);
		$this->config->getHash('foo+bar');
	}


	public function testBuildLocalModePlusSign(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setResources(['foo' => '/assets/foo.js', 'waldo' => '/assets/waldo.js']);
		$this->config->setLocalMode(LocalMode::Build);
		Assert::same('sha256-+vECTQha7Zz09xOwEIPocbCG8b+A2g5cjgSEAwSTzWY=', $this->config->getHash(['foo', 'baz', 'waldo']));
		Assert::true(file_exists($this->tempDir . '/-vECTQha7Zz09xOwEIPocbCG8b-A2g5cjgSEAwSTzWY.js'));
	}


	public function testBuildLocalModeStringResourceOnly(): void
	{
		$this->config->setHashingAlgos(['sha256']);
		$this->config->setLocalMode(LocalMode::Build);
		Assert::exception(function() {
			$this->config->getHash('foobar');
		}, UnknownExtensionException::class);
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $this->config->getHash('foobar', HtmlElement::Script));
		Assert::same('sha256-w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI=', $this->config->getHash(['foo', 'bar'], HtmlElement::Link));
		Assert::same("/{$this->buildDir}/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.js", $this->config->getUrl('foobar', HtmlElement::Script));
		Assert::same("/{$this->buildDir}/w6uP8Tcg6K2QR905Rms8iXTlksL6OD1KOWBxTK7wxPI.css", $this->config->getUrl(['foo', 'bar'], HtmlElement::Link));
	}


	public function testSetLocalMode(): void
	{
		Assert::noError(function (): void {
			$this->config->setLocalMode(LocalMode::Build);
			$this->config->setLocalMode(LocalMode::Direct);
			$this->config->setLocalMode('build');
			$this->config->setLocalMode('direct');
		});
	}


	public function testSetLocalModeInvalid(): void
	{
		Assert::throws(
			function (): void {
				$this->config->setLocalMode('foo');
			},
			ValueError::class,
			PHP_VERSION_ID < 80200 ? '"foo" is not a valid backing value for enum "Spaze\SubresourceIntegrity\LocalMode"' : '"foo" is not a valid backing value for enum Spaze\SubresourceIntegrity\LocalMode',
		);
	}


	public function testGetSetLocalPrefixes(): void
	{
		Assert::same("./$this->buildDir", $this->config->getLocalPathBuildPrefix());

		$this->config->setLocalBuildPrefix('foobar');
		Assert::same('./foobar', $this->config->getLocalPathBuildPrefix());

		$this->config->setLocalPrefix((object)['path' => '/what/ever/', 'build' => '/lala/land/']);
		Assert::same('/what/ever/lala/land', $this->config->getLocalPathBuildPrefix());

		$this->config->setLocalPrefix((object)['path' => 'what/ever', 'build' => 'lala/land']);
		Assert::same('what/ever/lala/land', $this->config->getLocalPathBuildPrefix());
	}

}

(new ConfigTest())->run();
