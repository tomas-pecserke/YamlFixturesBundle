<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Fixture;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use LogicException;
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FixtureArrayDataFixtureTest extends TestCase {
    /**
     * @var ReferenceRepository|MockObject
     */
    private $referenceRepository;

    /**
     * @var FixtureArrayDataLoaderInterface|MockObject
     */
    private $loader;

    /**
     * @var ObjectManager|MockObject
     */
    private $manager;

    /**
     * @var FixtureArrayDataFixture
     */
    private $fixture;

    protected function setUp(): void {
        $this->loader = $this->getMockForAbstractClass(FixtureArrayDataLoaderInterface::class);
        $this->manager = $this->getMockForAbstractClass(ObjectManager::class);
        $this->referenceRepository = new ReferenceRepository($this->manager);
        $this->fixture = new class extends FixtureArrayDataFixture {};
    }

    public function test_load_willCallLoader(): void {
        $data = ['test' => 'data'];

        $this->fixture->setReferenceRepository($this->referenceRepository);
        $this->fixture->setLoader($this->loader);
        $this->fixture->setFixtureData($data);

        $this->loader->expects($this->once())
            ->method('load')
            ->with($this->equalTo($this->manager), $this->equalTo($this->referenceRepository), $this->equalTo($data));

        $this->fixture->load($this->manager);
    }

    public function test_load_withoutReferenceRepository_willThrowException(): void {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Reference repository was not set');

        $this->fixture->setLoader($this->loader);
        $this->fixture->setFixtureData([]);

        $this->fixture->load($this->manager);
    }

    public function test_load_withoutLoader_willThrowException(): void {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Loader was not set');

        $this->fixture->setReferenceRepository($this->referenceRepository);
        $this->fixture->setFixtureData([]);

        $this->fixture->load($this->manager);
    }

    public function test_load_withoutFixtureData_willThrowException(): void {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Fixture data were not set');

        $this->fixture->setReferenceRepository($this->referenceRepository);
        $this->fixture->setLoader($this->loader);

        $this->fixture->load($this->manager);
    }
}
