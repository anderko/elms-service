<?php declare(strict_types=1);

namespace PremekKoch\Elms\DI;

use Nette\DI\CompilerExtension;
use PremekKoch\Elms\ElmsService;

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
            ->setFactory(ElmsService::class, [$config['orderSourceCode'], $config['debugMode']]);
    }
}
