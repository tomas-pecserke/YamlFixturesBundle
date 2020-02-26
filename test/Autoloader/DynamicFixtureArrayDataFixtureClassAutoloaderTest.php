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

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use PHPUnit\Framework\TestCase;

class DynamicFixtureArrayDataFixtureClassAutoloaderTest extends TestCase {
    private const CLASS_NAME_PREFIX = 'Test\\Fixtures\\';
    
    public function testLoadClassRegistered(): void {
        $className = self::CLASS_NAME_PREFIX . 'Registered';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, false, false);

        $this->assertFalse(class_exists($className));
        $autoloader->loadClass($className);
        $this->assertTrue(class_exists($className));
    }

    public function testLoadClassNotRegistered(): void {
        $className = self::CLASS_NAME_PREFIX . 'NonRegistered';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();

        $this->assertFalse(class_exists($className));
        $autoloader->loadClass($className);
        $this->assertFalse(class_exists($className));
    }

    public function testLoadClassOrdered(): void {
        $className = self::CLASS_NAME_PREFIX . 'Ordered';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, true, false);
        $autoloader->loadClass($className);

        $this->assertIsSubclassOf(OrderedFixtureInterface::class, $className);
    }

    public function testLoadClassNonOrdered(): void {
        $className = self::CLASS_NAME_PREFIX . 'NonOrdered';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, false, false);
        $autoloader->loadClass($className);

        $this->assertIsNotSubclassOf(OrderedFixtureInterface::class, $className);
    }

    public function testLoadClassDependent(): void {
        $className = self::CLASS_NAME_PREFIX . 'Dependent';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, false, true);
        $autoloader->loadClass($className);

        $this->assertIsSubclassOf(DependentFixtureInterface::class, $className);
    }

    public function testLoadClassNonDependent(): void {
        $className = self::CLASS_NAME_PREFIX . 'NonDependent';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, false, false);
        $autoloader->loadClass($className);

        $this->assertIsNotSubclassOf(DependentFixtureInterface::class, $className);
    }

    public function testLoadClassOrderedDependent(): void {
        $className = self::CLASS_NAME_PREFIX . 'OrderedDependent';
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $autoloader->registerFixtureClass($className, true, true);
        $autoloader->loadClass($className);

        $this->assertIsSubclassOf(DependentFixtureInterface::class, $className);
        $this->assertIsSubclassOf(OrderedFixtureInterface::class, $className);
    }

    private function assertIsSubclassOf(string $expectedClassName, string $className): void {
        $this->assertTrue(
            is_a($className, $expectedClassName, true),
            sprintf("Class '%s' is not a subclass of '%s'.", $className, $expectedClassName)
        );
    }

    private function assertIsNotSubclassOf(string $expectedClassName, string $className): void {
        $this->assertFalse(
            is_a($className, $expectedClassName, true),
            sprintf("Class '%s' is a subclass of '%s'.", $className, $expectedClassName)
        );
    }
}
