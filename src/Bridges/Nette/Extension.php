<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Nette;

use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Spaze\SubresourceIntegrity\Bridges\Latte\Macros;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\FileBuilder;
use stdClass;

class Extension extends CompilerExtension
{

	/** @var stdClass */
	protected $config;


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'resources' => Expect::anyOf(
				Expect::arrayOf(Expect::string()),
				Expect::structure([
					'url' => Expect::string(),
					'hash' => Expect::anyOf(
						Expect::string(),
						Expect::listOf(Expect::string()),
					),
				]),
			)->required(),
			'localPrefix' => Expect::structure([
				'url' => Expect::string(),
				'path' => Expect::string(),
				'build' => Expect::string(),
			])->required(),
			'localMode' => Expect::anyOf(Config::MODE_DIRECT, Config::MODE_BUILD)->default(Config::MODE_DIRECT),
			'hashingAlgos' => Expect::listOf(Expect::string()),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$sriConfig = $builder->addDefinition($this->prefix('config'))
			->setClass(Config::class)
			->addSetup('setResources', [$this->config->resources])
			->addSetup('setLocalPrefix', [$this->config->localPrefix])
			->addSetup('setLocalMode', [$this->config->localMode])
			->addSetup('setHashingAlgos', [$this->config->hashingAlgos]);

		$macros = $builder->addDefinition($this->prefix('macros'))
			->setClass(Macros::class);

		$macros = $builder->addDefinition($this->prefix('fileBuilder'))
			->setClass(FileBuilder::class);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$latteFactoryService = $builder->getByType(ILatteFactory::class) ?: 'nette.latteFactory';
		/** @var FactoryDefinition $service */
		$service = $builder->getDefinition($latteFactoryService);
		$service->getResultDefinition()->addSetup('?->onCompile[] = function (Latte\Engine $engine): void { $this->getByType(?)->install($engine->getCompiler()); }', ['@self', Macros::class]);
	}

}
