<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DataFixtures\Locator;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleResourcesLocator implements LocatorInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Returns an array of paths to fixture definition files found in specified bundle.
     *
     * Bundle's fixtures are located in /path/to/bundle/Resources/fixtures or in
     * /application/root/path/Resources/bundleName/fixtures.
     * Those under application root path take precedents over those under bundle path.
     *
     * @param string $bundleName
     * @return array
     */
    public function find($bundleName)
    {
        $bundleDir = $this->kernel->getBundle($bundleName)->getPath() . '/Resources/fixtures';
        $appDir = $this->kernel->getRootDir() . '/Resources/' . $bundleName . '/fixtures';

        $bundleFixtureFiles = array();
        if (is_dir($bundleDir)) {
            $finder = new Finder();
            $finder->files()->name('*.yml')->in($bundleDir);
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
            $finder->files()->name('*.yml')->in($appDir);
            /* @var SplFileInfo $file */
            foreach ($finder as $file) {
                $bundleFixtureFiles[] = $file->getPathname();
            }
        }

        // sort by relative path names
        usort($bundleFixtureFiles, function($a, $b) use ($bundleDir, $appDir) {
            return call_user_func_array('strcmp', str_replace(array($bundleDir, $appDir), '', array($a, $b)));
        });

        return $bundleFixtureFiles;
    }
}
