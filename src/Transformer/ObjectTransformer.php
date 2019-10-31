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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ObjectTransformer implements ObjectTransformerInterface {
    /**
     * Transforms an associative array into an object of specified class.
     *
     * For each key of the array public property is set if exists,
     * otherwise the setter or add method is called if exists.
     * If none of the above applies, an NoSuchPropertyException is thrown.
     *
     * @param array $data transformation data
     * @param string transformed object class name
     * @return object transformed object
     * @throws NoSuchPropertyException if property cannot be set
     * @throws InvalidArgumentException if class does not exist
     */
    public function transform(array $data, string $className): object {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class '$className' does not exist");
        }

        $object = new $className();

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            $accessor->setValue($object, (string)$property, $value);
        }

        return $object;
    }
}
