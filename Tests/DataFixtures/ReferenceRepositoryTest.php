<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use BadMethodCallException;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use PHPUnit\Framework\TestCase;
use stdClass;

class ReferenceRepositoryTest extends TestCase {
    public function testAddReference() {
        $repository = new ReferenceRepository();
        $object = new stdClass();
        $repository->addReference('test', $object);

        $this->assertSame($object, $repository->getReference('test'));
    }

    public function testAddReferenceAlreadyExists() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Reference to: (test) already exists, use method setReference in order to override it');

        $repository = new ReferenceRepository();
        $repository->addReference('test', new stdClass());
        $repository->addReference('test', new stdClass());
    }

    public function testSetReference() {
        $repository = new ReferenceRepository();
        $object = new stdClass();
        $repository->setReference('test', $object);

        $this->assertSame($object, $repository->getReference('test'));
    }

    public function testSetReferenceExists() {
        $repository = new ReferenceRepository();
        $object = new stdClass();
        $repository->setReference('test', new stdClass());
        $repository->setReference('test', $object);

        $this->assertSame($object, $repository->getReference('test'));
    }

    public function testGetReferenceByIdentity() {
        $repository = new ReferenceRepository();
        $object = new stdClass();
        $repository->setReferenceIdentity('test', $object);

        $this->assertSame($object, $repository->getReference('test'));
    }
}
