<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Pecserke\YamlFixturesBundle\DataFixtures\FixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\DataFixtures\FixtureArrayDataLoaderInterface;
use PHPUnit\Framework\TestCase;

class FixtureArrayDataFixtureTest extends TestCase {
    public function testLoad(): void {
        $data = ['test' => 'data'];
        $loader = $this->getMockForAbstractClass(FixtureArrayDataLoaderInterface::class);
        $manager = $this->getMockForAbstractClass(ObjectManager::class);
        $repo = $this->createMock(ReferenceRepository::class);
        $fixture = new FixtureArrayDataFixture($loader, $data);
        $fixture->setReferenceRepository($repo);

        $loader->expects($this->once())
            ->method('load')
            ->with($this->equalTo($manager), $this->equalTo($repo), $this->equalTo($data));

        $fixture->load($manager);
    }
}
