<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Parser;

use Closure;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface;
use Pecserke\YamlFixturesBundle\DataTransformer\PropertyValueTransformerInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class FixtureDataConfiguration implements ConfigurationInterface {
    public function getConfigTreeBuilder(): TreeBuilder {
        $treeBuilder = new TreeBuilder('fixtures');
        $rootNode = $treeBuilder->getRootNode();

        /** @noinspection NullPointerExceptionInspection */
        $rootNode
            ->arrayPrototype()
                ->children()
                    ->scalarNode('class')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(Closure::fromCallable([self::class, 'isClassInvalid']))
                            ->thenInvalid('Class %s does not exist')
                        ->end()
                    ->end()
                    ->integerNode('order')->defaultNull()->end()
                    ->arrayNode('equal_condition')
                        ->scalarPrototype()->cannotBeEmpty()->end()
                        ->beforeNormalization()->castToArray()->end()
                    ->end()
                    ->scalarNode('transformer')
                        ->defaultNull()
                        ->validate()
                            ->ifTrue(self::isServiceReferenceOrClassInvalidClosure(ObjectTransformerInterface::class))
                            ->thenInvalid('Invalid object transformer: %s')
                        ->end()
                    ->end()
                    ->arrayNode('data')
                        ->beforeNormalization()
                            ->ifArray()->then(Closure::fromCallable([self::class, 'normalizeClassDataArray']))
                        ->end()
                        ->arrayPrototype()
                            ->beforeNormalization()
                                ->ifArray()->then(Closure::fromCallable([self::class, 'normalizeSingleObjectDataArray']))
                            ->end()
                            ->children()
                                ->scalarNode('@reference')->end()
                                ->arrayNode('properties')
                                    ->useAttributeAsKey('name')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('@transformer')
                                                ->defaultNull()
                                                ->validate()
                                                    ->ifTrue(self::isServiceReferenceOrClassInvalidClosure(PropertyValueTransformerInterface::class))
                                                    ->thenInvalid('Invalid property value transformer: %s')
                                                ->end()
                                            ->end()
                                            ->scalarNode('name')->cannotBeEmpty()->end()
                                            ->variableNode('value')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public static function isClassInvalid(string $class): bool {
        return !class_exists($class);
    }

    public static function isServiceReferenceOrClassInvalidClosure(string $className): Closure {
        return static function ($value) use ($className) {
            return self::isServiceReferenceOrClassInvalid($value, $className);
        };
    }

    public static function isServiceReferenceOrClassInvalid($value, string $className): bool {
        return !is_string($value) || !(strpos($value, '@') === 0 || is_a($value, $className, true));
    }

    public static function normalizeClassDataArray(array $data): array {
        $result = [];
        foreach ($data as $key => $value) {
            if (empty($value['@reference']) && is_string($key)) {
                $value['@reference'] = $key;
            }
            $result[] = $value;
        }

        return $result;
    }

    public static function normalizeSingleObjectDataArray(array $data): array {
        $result = [];
        if (isset($data['@reference'])) {
            $result['@reference'] = $data['@reference'];
            unset($data['@reference']);
        }

        $properties = [];
        foreach ($data as $propertyName => $propertyValue) {
            if (!is_array($propertyValue) || !isset($propertyValue['value'])) {
                $propertyValue = ['value' => $propertyValue];
            }
            if (!isset($propertyValue['name']) && is_string($propertyName)) {
                $propertyValue['name'] = $propertyName;
            }
            $properties[] = $propertyValue;
        }
        $result['properties'] = $properties;

        return $result;
    }
}
