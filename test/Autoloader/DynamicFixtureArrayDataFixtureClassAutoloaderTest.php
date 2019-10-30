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

use PHPUnit\Framework\TestCase;

class DynamicFixtureArrayDataFixtureClassAutoloaderTest extends TestCase {
    /**
     * @dataProvider loadClassDataProvider
     */
    public function testLoadClass(string $className, bool $shouldLoad): void {
        $autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();

        $this->assertFalse(class_exists($className), "Class '$className' should be loaded before test.");

        $autoloader->loadClass($className);

        if ($shouldLoad) {
            $this->assertTrue(class_exists($className), "Class '$className' should be loaded, but is not.");
        } else {
            $this->assertFalse(class_exists($className), "Class '$className' should not be loaded, but is.");
        }
    }

    public function loadClassDataProvider(): array {
        return [
            'MatchingClassWillBeLoaded' => [
                'className' => DynamicFixtureArrayDataFixtureClassAutoloader::CLASS_NAME_BASE . '_Test' . time(),
                'shouldLoad' => true
            ],
            'NonMatchingClassWillNotBeLoaded' => [
                'className' => 'This\\Class\\Name\\Does\\Not\\Match',
                'shouldLoad' => false
            ]
        ];
    }
}
