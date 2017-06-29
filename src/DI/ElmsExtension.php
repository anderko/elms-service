<?php

namespace PremekKoch\Elms\DI;

use Nette\DI\CompilerExtension;


class ElmsExtension extends CompilerExtension
{
	public $defaults = [
		'orderSourceCode' => '',
		'debugMode' => false,
	];


	public function loadConfiguration(): void
	{

		$config = $this->config;
		$config += $this->defaults;

		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('elmsService'))
			->setClass('PremekKoch\Elms\ElmsService', [$config['orderSourceCode'], $config['debugMode']]);
	}
}
