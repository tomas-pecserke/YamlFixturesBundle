<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class FixtureArrayDataFixture implements FixtureInterface, SharedFixtureInterface {
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

    public function __construct(FixtureArrayDataLoaderInterface $loader, array $fixtureData) {
        $this->loader = $loader;
        $this->fixtureData = $fixtureData;
    }

    public function setReferenceRepository(ReferenceRepository $referenceRepository): void {
        $this->referenceRepository = $referenceRepository;
    }

    public function load(ObjectManager $manager): void {
        $this->loader->load($manager, $this->referenceRepository, $this->fixtureData);
    }
}
