<?php
namespace Publero\YamlFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Common\Persistence\ObjectRepository;

class InMemoryRepository implements ObjectRepository
{
    private $manager;
    private $className;

    /**
     * @param InMemoryObjectManager $manager
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function __construct(InMemoryObjectManager $manager, $className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("class '$className' does not exist");
        }

        $this->manager = $manager;
        $this->className = $className;
    }

    public function find($id)
    {
        return $this->manager->find($className, $id);
    }

    public function findAll()
    {
        $this->manager->all($this->className);
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return array_filter(
            $objects = $this->findAll() ?: array(),
            function($object) use ($criteria) {
                $public = get_object_vars($object);
                foreach ($criteria as $property => $value) {
                    $getMethodName = 'get' . ucfirst($property);
                    $isMethodName = 'is' . ucfirst($property);
                    if (in_array($property, $public)) {
                        if ($value !== $object->${$property}) {
                            return false;
                        }
                    } else if (method_exists($object, $getMethodName)) {
                        if ($value !== $object->$getMethodName()) {
                            return false;
                        }
                    } else if (method_exists($object, $isMethodName)) {
                        if ($value !== $object->$isMethodName()) {
                            return false;
                        }
                    }
                }

                return true;
            }
        );
    }

    public function findOneBy(array $criteria)
    {
        $result = $this->findBy($criteria);

        if (empty($result)) {
            return null;
        }
        if (count($result) === 1) {
            return $result[0];
        }
        throw new \InvalidArgumentException('more than ino result fot specified criteria');
    }

    public function getClassName()
    {
        return $this->className;
    }

}
