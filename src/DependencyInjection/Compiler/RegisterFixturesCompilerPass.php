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
use Pecserke\YamlFixturesBundle\Fixture\FixtureLocator;
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
    public function process(ContainerBuilder $container): void {
        $locator = new FixtureLocator();

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
        $configuration = new FixtureDataConfiguration();
        $config = $processor->processConfiguration($configuration, $fixtures);

        $i = 0;
        foreach ($config as $fixtureData) {
            $definition = new Definition();
            $definition->addMethodCall('setLoader', [new Reference(FixtureArrayDataLoaderInterface::class)]);
            $definition->addMethodCall('setFixtureData', [$fixtureData]);

            $classname = DynamicFixtureArrayDataFixtureClassAutoloader::CLASS_NAME_BASE;
            if ($fixtureData['order']) {
                $classname .= DynamicFixtureArrayDataFixtureClassAutoloader::ORDERED_SUFFIX;
                $definition->addMethodCall('setOrder', [$fixtureData['order']]);
            }

            $definition->setClass($classname . '_' . $i++);
            $definition->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $container->setDefinition($definition->getClass(), $definition);
        }
    }
}
