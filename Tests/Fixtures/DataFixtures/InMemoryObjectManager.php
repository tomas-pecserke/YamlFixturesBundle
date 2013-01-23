<?php
namespace Publero\YamlFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InMemoryObjectManager implements ObjectManager
{
    private static $count = 0;

    private $objects;

    public function __construct()
    {
        $this->objects = array();
    }

    public function find($className, $id)
    {
        if ($id < 0 || self::$count < $id) {
            return null;
        }

        if (array_key_exists($className, $this->objects)) {
            foreach ($this->objects[$className] as $object) {
                if ($object->getId() === $id) {
                    return $object;
                }
            }
        }

        return null;
    }

    public function persist($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object is not an object');
        }

        $class = get_class($object);
        $property = (new \ReflectionClass($class))->getProperty('id');

        $property->setAccessible(true);
        $property->setValue($object, self::$count);
        $property->setAccessible(false);
        self::$count++;

        $this->objects[$class][] = $object;
    }

    public function remove($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object is not an object');
        }

        $class = get_class($object);
        if (array_key_exists($class, $this->objects)) {
            foreach ($this->objects[$class] as $key => $o) {
                if ($object->getId() === $o->getId()) {
                    unset($this->objects[$class][$key]);
                }
            }
        }
    }

    public function merge($object)
    {
        throw new \BadFunctionCallException('not implemented');
    }

    public function clear($objectName = null)
    {
        $this->objects = array();
    }

    public function detach($object)
    {
        $this->remove($object);
    }

    public function refresh($object)
    {
    }

    public function flush()
    {
    }

    public function getRepository($className)
    {
        return new InMemoryRepository($this, $className);
    }

    public function getClassMetadata($className)
    {
        throw new \BadFunctionCallException('not implemented');
    }

    public function getMetadataFactory()
    {
        throw new \BadFunctionCallException('not implemented');
    }

    public function initializeObject($obj)
    {
        throw new \BadFunctionCallException('not implemented');
    }

    public function contains($object)
    {
        return $this->find(get_class($object), $object->getId()) !== null;
    }

    public function all($className = null)
    {
        if ($className !== null) {
            return array_key_exists($className, $this->objects) ? $this->objects[$className] : null;
        }

        return array_reduce(
            $this->objects,
            function ($result, $item) {
                return array_merge($result, $item);
            },
            array()
        );
    }
}
