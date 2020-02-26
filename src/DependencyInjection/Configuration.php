<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    public const CLASS_NAME_PREFIX = 'App\\Fixtures\\';

    public function getConfigTreeBuilder(): TreeBuilder {
        $treeBuilder = new TreeBuilder('pecserke_yaml_fixtures');

        /* @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        /* @noinspection NullPointerExceptionInspection */
        $rootNode
            ->children()
                ->scalarNode('fixture_class_prefix')
                    ->cannotBeEmpty()
                    ->defaultValue(self::CLASS_NAME_PREFIX)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
