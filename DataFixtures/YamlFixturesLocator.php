<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class YamlFixturesLocator {
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel) {
        $this->kernel = $kernel;
    }

    /**
     * Returns an array of file paths to YaML files in specified directory.
     *
     * @param string $directory
     * @return array
     * @throws InvalidArgumentException
     */
    public function findInDirectory($directory) {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("'$directory' isn't a directory or can noŧ read it");
        }

        $finder = new Finder();
        $finder->files()->name('*.yml')->in($directory)->sortByName();

        return array_map(
            function (SplFileInfo $file) {
                return $file->getPathname();
            },
            iterator_to_array($finder)
        );
    }

    /**
     * Returns an array of file paths to YaML files in specified bundle.
     *
     * Bundle's fixtures are located in /path/to/bundle/Resources/fixtures or in
     * /application/root/path/Resources/bundleName/fixtures.
     * Those under application root path take precedents over those under bundle path.
     *
     * @param string $bundleName
     * @return array
     * @throws InvalidArgumentException
     */
    public function findInBundle($bundleName) {
        $bundleDir = $this->kernel->getBundle($bundleName)->getPath() . '/Resources/fixtures';
        $appDir = $this->kernel->getRootDir() . '/Resources/' . $bundleName . '/fixtures';

        $bundleFixtureFiles = array();
        if (is_dir($bundleDir)) {
            $finder = new Finder();
            $finder->files()->name('*.yml')->in($bundleDir)->sortByName();
            /* @var SplFileInfo $file */
            foreach ($finder as $file) {
                $overrideFilename = $appDir . DIRECTORY_SEPARATOR . $file->getRelativePathname();
                if (!is_file($overrideFilename)) {
                    $bundleFixtureFiles[] = $file->getPathname();
                }
            }
        }

        if (is_dir($appDir)) {
            $finder = new Finder();
            $finder->files()->name('*.yml')->in($appDir)->sortByName();
            /* @var SplFileInfo $file */
            foreach ($finder as $file) {
                $bundleFixtureFiles[] = $file->getPathname();
            }
        }

        return $bundleFixtureFiles;
    }
}
