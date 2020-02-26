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

class OrderedFixtureArrayDataFixtureTest extends TestCase {
    public function test_getOrder_returnsValueThatWasSetBefore(): void {
        $fixture = new class extends OrderedFixtureArrayDataFixture {};

        $order = 1234;

        $fixture->setOrder($order);
        $this->assertEquals($order, $fixture->getOrder());
    }
}
