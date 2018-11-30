<?php

namespace Lychee\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lychee_core');

        $rootNode
            ->children()
                ->scalarNode('image_domain')->end()
                ->scalarNode('image_upload_path')->end()
                ->scalarNode('sensitive_words')->end()
                ->scalarNode('sensitive_words_encoding')->defaultValue('%kernel.charset%')->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSensitiveWordSection($rootNode) {

    }
}
