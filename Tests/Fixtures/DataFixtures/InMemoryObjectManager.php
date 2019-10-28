<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataFixtures;

use BadFunctionCallException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\PropertyChangedListener;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class InMemoryObjectManager implements ObjectManager {
    private static $count = 0;

    private $objects;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var PropertyChangedListener
     */
    private $unitOfWork;

    public function __construct() {
        $this->objects = array();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->unitOfWork = new InMemoryUnitOfWork();
    }

    public function find($className, $id) {
        if ($id < 0 || self::$count < $id) {
            return null;
        }

        if (array_key_exists($className, $this->objects)) {
            foreach ($this->objects[$className] as $object) {
                if ($this->propertyAccessor->getValue($object, 'id') === $id) {
                    return $object;
                }
            }
        }

        return null;
    }

    /**
     * @param object $object
     * @throws ReflectionException
     */
    public function persist($object): void {
        if (!is_object($object)) {
            throw new InvalidArgumentException('$object is not an object');
        }

        $class = get_class($object);
        $reflectionClass = new ReflectionClass($class);
        $property = $reflectionClass->getProperty('id');

        $property->setAccessible(true);
        $property->setValue($object, self::$count);
        $property->setAccessible(false);
        self::$count++;

        $this->objects[$class][] = $object;
    }

    public function remove($object): void {
        if (!is_object($object)) {
            throw new InvalidArgumentException('$object is not an object');
        }

        $class = get_class($object);
        if (array_key_exists($class, $this->objects)) {
            foreach ($this->objects[$class] as $key => $o) {
                if ($this->propertyAccessor->getValue($object, 'id') === $this->propertyAccessor->getValue($o, 'id')) {
                    unset($this->objects[$class][$key]);
                }
            }
        }
    }

    public function merge($object): void {
        throw new BadFunctionCallException('not implemented');
    }

    public function clear($objectName = null): void {
        $this->objects = array();
    }

    public function detach($object): void {
        $this->remove($object);
    }

    public function refresh($object): void {
    }

    public function flush(): void {
    }

    public function getRepository($className): InMemoryRepository {
        return new InMemoryRepository($this, $className);
    }

    public function getClassMetadata($className): ?ClassMetadata {
        return null;
    }

    public function getMetadataFactory(): ClassMetadataFactory {
        throw new BadFunctionCallException('not implemented');
    }

    public function initializeObject($obj): void {
        throw new BadFunctionCallException('not implemented');
    }

    public function contains($object): bool {
        return $this->find(get_class($object), $object->getId()) !== null;
    }

    public function all($className = null) {
        if ($className !== null) {
            return $this->objects[$className] ?? null;
        }

        return array_reduce(
            $this->objects,
            static function ($result, $item) {
                return array_merge($result, array_values($item));
            },
            array()
        );
    }

    public function getUnitOfWork(): PropertyChangedListener {
        return $this->unitOfWork;
    }
}
