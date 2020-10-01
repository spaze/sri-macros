<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Nette;

/**
 * SubresourceIntegrity\Config extension.
 *
 * @author Michal Špaček
 */
class Extension extends \Nette\DI\CompilerExtension
{

	/** @var array */
	public $defaults = array(
		'resources' => array(),
		'localPrefix' => array(
			'url' => '',
			'path' => '',
		),
		'localMode' => 'build',
		'hashingAlgos' => 'sha256',
	);


	public function loadConfiguration()
	{
		$this->validateConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$sriConfig = $builder->addDefinition($this->prefix('config'))
			->setClass(\Spaze\SubresourceIntegrity\Config::class)
			->addSetup('setResources', [$this->config['resources']])
			->addSetup('setLocalPrefix', [$this->config['localPrefix']])
			->addSetup('setLocalMode', [$this->config['localMode']])
			->addSetup('setHashingAlgos', [$this->config['hashingAlgos']]);

		$macros = $builder->addDefinition($this->prefix('macros'))
			->setClass(\Spaze\SubresourceIntegrity\Bridges\Latte\Macros::class);

		$macros = $builder->addDefinition($this->prefix('fileBuilder'))
			->setClass(\Spaze\SubresourceIntegrity\FileBuilder::class);
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$latteFactoryService = $builder->getByType(\Nette\Bridges\ApplicationLatte\ILatteFactory::class) ?: 'nette.latteFactory';
		/** @var \Nette\DI\Definitions\FactoryDefinition $service */
		$service = $builder->getDefinition($latteFactoryService);
		$service->getResultDefinition()->addSetup('?->onCompile[] = function (Latte\Engine $engine): void { $this->getByType(?)->install($engine->getCompiler()); }', ['@self', \Spaze\SubresourceIntegrity\Bridges\Latte\Macros::class]);
	}

}
