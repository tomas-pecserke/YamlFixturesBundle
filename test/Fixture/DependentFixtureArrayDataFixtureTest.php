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

use PHPUnit\Framework\TestCase;

class DependentFixtureArrayDataFixtureTest extends TestCase {
    public function test_getDependencies_returnsValueThatWasSetBefore(): void {
        $fixture = new class extends DependentFixtureArrayDataFixture {};

        $dependencies = ['1', '2', '3'];

        $fixture->setDependencies($dependencies);
        $this->assertEquals($dependencies, $fixture->getDependencies());
    }
}
