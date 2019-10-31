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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class FixtureLocatorTest extends TestCase {
    /**
     * @var FixtureLocator
     */
    private $locator;

    /**
     * @throws vfsStreamException
     */
    public function setUp(): void {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('locatorTestDir'));

        $this->locator = new FixtureLocator();
    }

    public function testFindFixturesInProjectDirectory(): void {
        $this->markTestIncomplete();
    }

    public function testFindFixturesInProjectDirectoryIfFixturesDirectoryDoesNotExistReturnsEmptyArray(): void {
        $rootDir = vfsStream::url('locatorTestDir');

        $this->assertEmpty($this->locator->findFixturesInProjectDirectory($rootDir));
    }

    public function testFindFixturesInBundle(): void {
        $this->markTestIncomplete();
    }
}
