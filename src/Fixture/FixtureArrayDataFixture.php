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
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use LogicException;
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;

abstract class FixtureArrayDataFixture implements SharedFixtureInterface {
    /**
     * @var FixtureArrayDataLoaderInterface
     */
    private $loader;

    /**
     * @var array fixture data parsed to an array
     */
    private $fixtureData;

    /**
     * @var ReferenceRepository
     */
    private $referenceRepository;

    public function setReferenceRepository(ReferenceRepository $referenceRepository): void {
        $this->referenceRepository = $referenceRepository;
    }

    public function setLoader(FixtureArrayDataLoaderInterface $loader): void {
        $this->loader = $loader;
    }

    public function getFixtureData(): array {
        if ($this->fixtureData === null) {
            throw new LogicException('Fixture data were not set');
        }

        return $this->fixtureData;
    }

    public function setFixtureData(array $fixtureData): void {
        $this->fixtureData = $fixtureData;
    }

    public function load(ObjectManager $manager): void {
        if ($this->referenceRepository === null) {
            throw new LogicException('Reference repository was not set');
        }
        if ($this->loader === null) {
            throw new LogicException('Loader was not set');
        }
        if ($this->fixtureData === null) {
            throw new LogicException('Fixture data were not set');
        }

        $this->loader->load($manager, $this->referenceRepository, $this->fixtureData);
    }
}
