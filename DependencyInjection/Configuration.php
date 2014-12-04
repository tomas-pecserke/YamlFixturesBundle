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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('pecserke_yaml_fixtures');

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('supported_registry_service_ids')
                    ->prototype('scalar')
                    ->defaultValue(array('doctrine', 'doctrine_mongodb', 'doctrine_phpcr'))
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
