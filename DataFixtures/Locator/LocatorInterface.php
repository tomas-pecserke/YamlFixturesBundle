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

interface LocatorInterface
{
    /**
     * Returns an array of paths to fixture definition files found at specified location.
     *
     * @param string $location
     * @return array
     */
    public function find($location);
}
