<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Finder;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class FixtureFinder {
    /**
     * @param string $projectRootDir
     * @return string[]
     */
    public function findFixturesInProjectDirectory(string $projectRootDir): array {
        $dir = self::getFixturesDir($projectRootDir);
        if (!is_dir($dir)) {
            return [];
        }

        $files = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($dir)->exclude(['bundles'])->getIterator();
        $paths = array_map(
            static function (SplFileInfo $file) {
                return $file->getPathname();
            },
            iterator_to_array($files)
        );

        return array_keys($paths);
    }

    /**
     * @param BundleInterface $bundle
     * @param string $projectRootDir
     * @return string[]
     */
    public function findFixturesInBundle(BundleInterface $bundle, string $projectRootDir): array {
        $fixtureFiles = [];

        $bundleFixturesDir = self::getBundleFixturesDir($bundle);
        if (is_dir($bundleFixturesDir)) {
            $finder = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($bundleFixturesDir);
            foreach ($finder->getIterator() as $file) {
                $fixtureFiles[$file->getRelativePathname()] = $file->getPathname();
            }
        }

        $overrideFixturesDir = self::getBundleOverrideFixturesDir($bundle, $projectRootDir);
        if (is_dir($overrideFixturesDir)) {
            $finder = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($overrideFixturesDir);
            foreach ($finder->getIterator() as $file) {
                $fixtureFiles[$file->getRelativePathname()] = $file->getPathname();
            }
        }

        return array_values($fixtureFiles);
    }

    private static function getFixturesDir(string $projectRootDir): string {
        return $projectRootDir . DIRECTORY_SEPARATOR . 'fixtures';
    }

    private static function getBundleFixturesDir(BundleInterface $bundle): string {
        return $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'fixtures';
    }

    private static function getBundleOverrideFixturesDir(BundleInterface $bundle, string $projectRootDir): string {
        return self::getFixturesDir($projectRootDir)
            . DIRECTORY_SEPARATOR . 'bundles'
            . DIRECTORY_SEPARATOR . $bundle->getName();
    }
}
