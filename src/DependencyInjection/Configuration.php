<?php

declare(strict_types=1);

namespace Fastfony\LicenceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fastfony_licence');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('key')->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
