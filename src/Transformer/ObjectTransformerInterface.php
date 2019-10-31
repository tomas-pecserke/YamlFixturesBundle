<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Transformer;

use InvalidArgumentException;

interface ObjectTransformerInterface {
    /**
     * Transforms an associative array into an object of specified class.
     *
     * For each key of the array public property is set if exists,
     * otherwise the setter or add method is called if exists.
     * If none of above applies, an InvalidArgumentException is thrown.
     *
     * @param array $data
     * @param string $className
     * @return object
     * @throws InvalidArgumentException
     */
    public function transform(array $data, string $className): object;
}
