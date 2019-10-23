<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Event;

use Pecserke\YamlFixturesBundle\Event\FixtureObjectEvent;
use PHPUnit\Framework\TestCase;
use stdClass;

class FixtureObjectEventTest extends TestCase {
    public function testConstructor() {
        $object = new stdClass();
        $fixtureName = 'testFixtureName';
        $event = new FixtureObjectEvent($object, $fixtureName);

        $this->assertSame($object, $event->getObject());
        $this->assertEquals($fixtureName, $fixtureName);
    }
}
