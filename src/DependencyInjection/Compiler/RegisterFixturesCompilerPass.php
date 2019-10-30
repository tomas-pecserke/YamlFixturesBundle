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
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;
use Pecserke\YamlFixturesBundle\Parser\FixtureDataConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class RegisterFixturesCompilerPass implements CompilerPassInterface {
    public function process(ContainerBuilder $container): void {
        $fixturesDir = self::getFixturesDir($container);
        $bundles = self::getBundles($container);
        $fixtures = [];

        if (is_dir($fixturesDir)) {
            $finder = Finder::create()->files()->name(['*.yml', '*.yaml'])->exclude(['bundles'])->in($fixturesDir);
            foreach ($finder->getIterator() as $file) {
                $fixtures[] = Yaml::parseFile($file->getRealPath());
            }
        }

        foreach ($bundles as $bundle) {
            $fixtureFiles = [];

            $bundleFixturesDir = self::getBundleFixturesDir($bundle);
            if (is_dir($bundleFixturesDir)) {
                $finder = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($bundleFixturesDir);
                foreach ($finder->getIterator() as $file) {
                    $fixtureFiles[$file->getRelativePathname()] = $file->getRealPath();
                }
            }

            $overrideFixturesDir = self::getBundleOverrideFixturesDir($bundle, $fixturesDir);
            if (is_dir($overrideFixturesDir)) {
                $finder = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($overrideFixturesDir);
                foreach ($finder->getIterator() as $file) {
                    $fixtureFiles[$file->getRelativePathname()] = $file->getRealPath();
                }
            }

            foreach ($fixtureFiles as $relativePath => $path) {
                $fixtures[] = Yaml::parseFile($path);
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

    private static function getFixturesDir(ContainerInterface $container): string {
        return $container->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'fixtures';
    }

    private static function getBundleFixturesDir(BundleInterface $bundle): string {
        return $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'fixtures';
    }

    private static function getBundleOverrideFixturesDir(BundleInterface $bundle, string $fixturesDir): string {
        return $fixturesDir . DIRECTORY_SEPARATOR . 'bundles' . DIRECTORY_SEPARATOR . $bundle->getName();
    }

    /**
     * @param ContainerInterface $container
     * @return BundleInterface[]
     */
    private static function getBundles(ContainerInterface $container): array {
        return $container->getParameter('kernel.bundles');
    }
}
