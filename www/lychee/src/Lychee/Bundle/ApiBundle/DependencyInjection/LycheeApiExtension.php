<?php

namespace Lychee\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class LycheeApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['LycheeWebsiteBundle']) === false) {
            $errorListener = new Definition('Lychee\Bundle\ApiBundle\EventListener\ErrorListener');
            $errorListener->addArgument(
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE, true)
            );
            if ($container->getParameter('report_system_error')) {
                $errorListener->addArgument(
                    new Reference('lychee.module.notification.push', ContainerInterface::IGNORE_ON_INVALID_REFERENCE, true)
                );
            } else {
                $errorListener->addArgument(null);
            }
            $errorListener->addArgument($container->getParameter('kernel.debug'));

            $errorListener->addTag('kernel.event_subscriber');
            $errorListener->addTag('monolog.logger', array('channel' => 'emergency'));
            $container->addDefinitions(array('lychee_api.error_listener' => $errorListener));
        }
    }
}
