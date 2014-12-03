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

class FilesystemLocator implements LocatorInterface
{
    /**
     * Returns an array of paths to fixture definition files found in specified directory.
     *
     * @param string $directory
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function find($directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("'$directory' is not a readable directory");
        }

        $finder = new Finder();
        $finder->files()->name('*.yml')->in($directory);

        $files = array_keys(iterator_to_array($finder));
        sort($files);

        return $files;
    }
}
