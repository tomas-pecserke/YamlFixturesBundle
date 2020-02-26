<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Pecserke\YamlFixturesBundle\Autoloader\DynamicFixtureArrayDataFixtureClassAutoloader;
use Pecserke\YamlFixturesBundle\Finder\FixtureFinder;
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;
use Pecserke\YamlFixturesBundle\Parser\FixtureDataConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class RegisterFixturesCompilerPass implements CompilerPassInterface {
    /**
     * @var DynamicFixtureArrayDataFixtureClassAutoloader
     */
    private $autoloader;

    public function __construct(DynamicFixtureArrayDataFixtureClassAutoloader $autoloader) {
        $this->autoloader = $autoloader;
    }

    public function process(ContainerBuilder $container): void {
        $classNamePrefix = $container->getParameter('pecserke_yaml_fixtures.fixture_class_prefix');
        $locator = new FixtureFinder();

        /* @var string $projectRootDir */
        $projectRootDir = $container->getParameter('kernel.project_dir');
        /* @var BundleInterface[] $bundles */
        $bundles = $container->getParameter('kernel.bundles');
        $fixtures = [];

        $projectFixtureFiles = $locator->findFixturesInProjectDirectory($projectRootDir);
        foreach ($projectFixtureFiles as $path) {
            $parsed = Yaml::parseFile($path);
            foreach (array_keys($parsed) as $key) {
                $parsed[$key]['file'] = $path;
            }
            $fixtures[] = $parsed;
        }

        foreach ($bundles as $bundle) {
            $bundleFixtureFiles = $locator->findFixturesInBundle($bundle, $projectRootDir);
            foreach ($bundleFixtureFiles as $path) {
                $parsed = Yaml::parseFile($path);
                foreach (array_keys($parsed) as $key) {
                    $parsed[$key]['file'] = $path;
                }
                $fixtures[] = $parsed;
            }
        }

        $processor = new Processor();
        $configuration = new FixtureDataConfiguration($classNamePrefix);
        $config = $processor->processConfiguration($configuration, $fixtures);

        foreach ($config as $fixtureData) {
            $classname = static::getFixtureClassName($fixtureData, $classNamePrefix);
            $ordered = isset($fixtureData['order']);
            $dependent = !empty($fixtureData['dependencies']);
            $this->autoloader->registerFixtureClass($classname, $ordered, $dependent);

            $definition = new Definition($classname);
            $definition->addMethodCall('setLoader', [new Reference(FixtureArrayDataLoaderInterface::class)]);
            $definition->addMethodCall('setFixtureData', [$fixtureData]);
            $definition->addTag(FixturesCompilerPass::FIXTURE_TAG);

            if ($ordered) {
                $definition->addMethodCall('setOrder', [$fixtureData['order']]);
            }
            if ($dependent) {
                $dependencyClasses = array_map(
                    static function (string $dependency) use ($classNamePrefix) {
                        return class_exists($dependency) ? $dependency : $classNamePrefix . $dependency;
                    },
                    $fixtureData['dependencies']
                );
                $definition->addMethodCall('setDependencies', [$dependencyClasses]);
            }

            $container->setDefinition($definition->getClass(), $definition);
        }
    }

    private static function getFixtureClassName(array $fixtureData, string $classNamePrefix): string {
        return $classNamePrefix . self::getFixtureName($fixtureData);
    }

    private static function getFixtureName(array $fixtureData): string {
        return !empty($fixtureData['set_name'])
            ? $fixtureData['set_name']
            : static::normalizeFileName($fixtureData['file']);
    }

    private static function normalizeFileName(string $filename): string {
        $filename = basename($filename);
        $index = strrpos($filename, '.');
        if ($index !== false) {
            $filename = substr($filename, 0, $index);
        }
        /* @noinspection NotOptimalRegularExpressionsInspection */
        preg_replace('/[^a-zA-Z0-9_]/', '_', $filename);

        return $filename;
    }
}
