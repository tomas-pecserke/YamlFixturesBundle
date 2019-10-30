<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Autoloader;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractAutoloaderTest extends TestCase {
    /**
     * @var AbstractAutoloader|MockObject
     */
    private $autoloader;

    protected function setUp(): void {
        $this->autoloader = $this->getMockForAbstractClass(AbstractAutoloader::class);
    }

    public function testIsRegisteredReturnsCorrectAnswer(): void {
        $this->assertFalse($this->autoloader->isRegistered(), 'Autoloader should not be registered before test.');
        $this->autoloader->register();
        $this->assertTrue($this->autoloader->isRegistered(), 'Autoloader should be registered after calling register function.');
        $this->autoloader->unregister();
        $this->assertFalse($this->autoloader->isRegistered(), 'Autoloader should not be registered after calling unregister function.');
    }

    public function testRegisteredAutoloaderIsCallsLoadClassFunction(): void {
        $className = 'Not\Loaded\Class';

        $this->autoloader->expects($this->once())
            ->method('loadClass')
            ->with($className);

        $this->autoloader->register();
        class_exists($className, true);
    }

    public function testUnregisteredAutoloaderDoesNotCallLoadClassFunction(): void {
        $className = 'Not\Loaded\Class';

        $this->autoloader->expects($this->never())
            ->method('loadClass');

        $this->autoloader->register();
        $this->autoloader->unregister();
        class_exists($className, true);
    }
}
