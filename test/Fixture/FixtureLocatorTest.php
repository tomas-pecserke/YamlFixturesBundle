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
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

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
        $projectDir = vfsStream::url('locatorTestDir');
        $fixturesDir = $projectDir . '/fixtures';
        mkdir($fixturesDir);
        file_put_contents($fixturesDir . '/1.yml', '');
        mkdir($fixturesDir . '/sub');
        file_put_contents($fixturesDir . '/sub/2.yml', '');
        file_put_contents($fixturesDir . '/sub/1.yml', '');
        mkdir($fixturesDir . '/bundles/TestBundle', 0777, true);
        file_put_contents($fixturesDir . '/bundles/TestBundle/bundle.yml', '');

        $files = $this->locator->findFixturesInProjectDirectory($projectDir);
        sort($files);

        $expected = [$fixturesDir . '/1.yml', $fixturesDir . '/sub/1.yml', $fixturesDir . '/sub/2.yml'];
        sort($expected);

        $this->assertEquals($expected, $files);
    }

    public function testFindFixturesInProjectDirectoryIfFixturesDirectoryDoesNotExistReturnsEmptyArray(): void {
        $rootDir = vfsStream::url('locatorTestDir');

        $this->assertEmpty($this->locator->findFixturesInProjectDirectory($rootDir));
    }

    public function testFindFixturesInBundleInBundleDir(): void {
        $bundle = $this->getBundleMock();
        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        $projectDir = vfsStream::url('locatorTestDir') . '/project';

        mkdir($bundleResourceDir, 0777, true);
        file_put_contents($bundleResourceDir . '/1.yml', '');
        mkdir($bundleResourceDir . '/sub');
        file_put_contents($bundleResourceDir . '/sub/1.yml', '');
        file_put_contents($bundleResourceDir . '/sub/2.yml', '');

        $files = $this->locator->findFixturesInBundle($bundle, $projectDir);

        $this->assertCount(3, $files);
        $this->assertContains($bundleResourceDir . '/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/2.yml', $files);
    }

    public function testFindFixturesInBundleProjectDir(): void {
        $bundle = $this->getBundleMock();
        $projectDir = vfsStream::url('locatorTestDir') . '/project';
        $projectResourceDir = $projectDir . '/fixtures/bundles/' . $bundle->getName();

        mkdir($projectResourceDir, 0777, true);
        file_put_contents($projectResourceDir . '/1.yml', '');
        mkdir($projectResourceDir . '/sub');
        file_put_contents($projectResourceDir . '/sub/1.yml', '');
        file_put_contents($projectResourceDir . '/sub/2.yml', '');

        $files = $this->locator->findFixturesInBundle($bundle, $projectDir);

        $this->assertCount(3, $files);
        $this->assertContains($projectResourceDir . '/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/2.yml', $files);
    }

    public function testFindInBundleInBundleOverride() {
        $bundle = $this->getBundleMock();
        $projectDir = vfsStream::url('locatorTestDir') . '/project';
        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        mkdir($bundleResourceDir, 0777, true);
        $projectResourceDir = $projectDir . '/fixtures/bundles/' . $bundle->getName();
        mkdir($projectResourceDir, 0777, true);
        file_put_contents($bundleResourceDir . '/1.yml', '');
        file_put_contents($projectResourceDir . '/1.yml', '');
        mkdir($bundleResourceDir . '/sub');
        mkdir($projectResourceDir . '/sub');
        file_put_contents($bundleResourceDir . '/sub/1.yml', '');
        file_put_contents($projectResourceDir . '/sub/1.yml', '');
        file_put_contents($bundleResourceDir . '/sub/2.yml', '');

        $files = $this->locator->findFixturesInBundle($bundle, $projectDir);

        $this->assertCount(3, $files);
        $this->assertContains($projectResourceDir . '/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/2.yml', $files);
    }

    private function getBundleMock(): BundleInterface {
        $bundleDir = vfsStream::url('locatorTestDir') . '/bundle';

        $bundle = $this->getMockForAbstractClass(BundleInterface::class);
        $bundle->method('getPath')->willReturn($bundleDir);
        $bundle->method('getName')->willReturn('TestBundle');

        return $bundle;
    }
}
