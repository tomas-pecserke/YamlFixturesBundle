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

use Doctrine\Common\Persistence\ObjectRepository;
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class InMemoryRepository implements ObjectRepository {
    private $manager;
    private $className;

    /**
     * @param InMemoryObjectManager $manager
     * @param string $className
     * @throws InvalidArgumentException
     */
    public function __construct(InMemoryObjectManager $manager, $className) {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("class '$className' does not exist");
        }

        $this->manager = $manager;
        $this->className = $className;
    }

    public function find($id) {
        return $this->manager->find($this->className, $id);
    }

    public function findAll() {
        return $this->manager->all($this->className);
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
        $accessor = PropertyAccess::createPropertyAccessor();

        return array_filter(
            $objects = $this->findAll() ?: array(),
            function ($object) use ($criteria, $accessor) {
                foreach ($criteria as $property => $value) {
                    if ($value != $accessor->getValue($object, $property)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    public function findOneBy(array $criteria) {
        $result = $this->findBy($criteria);

        if (empty($result)) {
            return null;
        }
        if (count($result) === 1) {
            return $result[0];
        }
        throw new InvalidArgumentException('more than ino result fot specified criteria');
    }

    public function getClassName() {
        return $this->className;
    }
}
