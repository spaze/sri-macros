<?php
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
	);


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$sriConfig = $builder->addDefinition($this->prefix('config'))
			->setClass(\Spaze\SubresourceIntegrity\Config::class)
			->addSetup('setResources', array($config['resources']))
			->addSetup('setLocalPrefix', array($config['localPrefix']));

		$macros = $builder->addDefinition($this->prefix('macros'))
			->setClass(\Spaze\SubresourceIntegrity\Bridges\Latte\Macros::class);
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$register = function (\Nette\DI\ServiceDefinition $service) {
			$service->addSetup('?->onCompile[] = function ($engine) { $this->getByType(\Spaze\SubresourceIntegrity\Bridges\Latte\Macros::class)->install($engine->getCompiler()); }', ['@self']);
		};

		$latteFactoryService = $builder->getByType(\Nette\Bridges\ApplicationLatte\ILatteFactory::class) ?: 'nette.latteFactory';
		if ($builder->hasDefinition($latteFactoryService)) {
			$register($builder->getDefinition($latteFactoryService));
		}

		if ($builder->hasDefinition('nette.latte')) {
			$register($builder->getDefinition('nette.latte'));
		}
	}

}
