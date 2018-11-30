<?php

namespace Lychee\Bundle\ApiBundle;

use Lychee\ApiBundle\DependencyInjection\Compiler\AddExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\GraphvizDumper;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LycheeApiBundle extends Bundle {

    public function build(ContainerBuilder $container) {
        parent::build($container);

//        if ($container->hasExtension('framework')) {
//            $routerConfig = array('router' => array(
//                'resource' => $this->getPath().'/Resources/config/routing.yml',
//                'strict_requirements' => false,
//            ));
//            $container->prependExtensionConfig('framework', $routerConfig);
//        }
//
//        $container->addCompilerPass(new AddExtensionConfigurationPass());
    }

}
