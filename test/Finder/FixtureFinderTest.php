<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Finder;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class FixtureFinderTest extends TestCase {
    private const BUNDLE_NAME = 'TestBundle';

    /**
     * @var FixtureFinder
     */
    private $locator;

    /**
     * @var vfsStreamDirectory
     */
    private $bundleDir;

    /**
     * @var vfsStreamDirectory
     */
    private $projectDir;

    /**
     * @throws vfsStreamException
     */
    public function setUp(): void {
        $rootDir = vfsStream::setup('locatorTestDir', null, ['bundle' => [], 'project' => []]);
        $this->bundleDir = $rootDir->getChild('bundle');
        $this->projectDir = $rootDir->getChild('project');
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot($rootDir);

        $this->locator = new FixtureFinder();
    }

    public function testFindFixturesInProjectDirectory(): void {
        $fileStructure = ['fixtures' => [
            'bundles' => ['TestBundle' => ['1.yml' => '']],
            '1.yml' => '',
            'sub' => ['1.yml' => '', '2.yml' => '']
        ]];
        vfsStream::create($fileStructure, $this->projectDir);
        $rootDirPath = $this->projectDir->url();

        $files = $this->locator->findFixturesInProjectDirectory($rootDirPath);

        $this->assertCount(3, $files);
        $this->assertContains($rootDirPath . '/fixtures/1.yml', $files);
        $this->assertContains($rootDirPath . '/fixtures/sub/1.yml', $files);
        $this->assertContains($rootDirPath . '/fixtures/sub/2.yml', $files);
    }

    public function testFindFixturesInProjectDirectoryIfFixturesDirectoryDoesNotExistReturnsEmptyArray(): void {
        $this->assertEmpty($this->locator->findFixturesInProjectDirectory($this->projectDir->url()));
    }

    public function testFindFixturesInBundleInBundleDir(): void {
        $bundle = $this->getBundleMock();

        $fileStructure = ['Resources' => ['fixtures' => [
            '1.yml' => '',
            'sub' => ['1.yml' => '', '2.yml' => '']
        ]]];
        vfsStream::create($fileStructure, $this->bundleDir);

        $files = $this->locator->findFixturesInBundle($bundle, $this->projectDir->url());

        $this->assertCount(3, $files);

        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        $this->assertContains($bundleResourceDir . '/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/2.yml', $files);
    }

    public function testFindFixturesInBundleProjectDir(): void {
        $bundle = $this->getBundleMock();

        $fileStructure = ['fixtures' => ['bundles' => [
            $bundle->getName() => [
                '1.yml' => '',
                'sub' => ['1.yml' => '', '2.yml' => '']
            ],
            'OtherBundle' => ['this_one_should_not_be_found.yml' => '']
        ]]];
        vfsStream::create($fileStructure, $this->projectDir);

        $files = $this->locator->findFixturesInBundle($bundle, $this->projectDir->url());

        $this->assertCount(3, $files);

        $projectResourceDir = $this->projectDir->url() . '/fixtures/bundles/' . $bundle->getName();
        $this->assertContains($projectResourceDir . '/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/2.yml', $files);
    }

    public function testFindInBundleInBundleOverride(): void {
        $bundle = $this->getBundleMock();

        $projectStructure = ['fixtures' => ['bundles' => [$bundle->getName() => [
            '1.yml' => '',
            'sub' => ['1.yml' => '']
        ]]]];
        vfsStream::create($projectStructure, $this->projectDir);

        $bundleStructure = ['Resources' => ['fixtures' => [
            '1.yml' => '',
            'sub' => ['1.yml' => '', '2.yml' => '']
        ]]];
        vfsStream::create($bundleStructure, $this->bundleDir);

        $files = $this->locator->findFixturesInBundle($bundle, $this->projectDir->url());

        $this->assertCount(3, $files);

        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        $projectResourceDir = $this->projectDir->url() . '/fixtures/bundles/' . $bundle->getName();
        $this->assertContains($projectResourceDir . '/1.yml', $files);
        $this->assertContains($projectResourceDir . '/sub/1.yml', $files);
        $this->assertContains($bundleResourceDir . '/sub/2.yml', $files);
    }

    private function getBundleMock(): BundleInterface {
        $bundle = $this->getMockForAbstractClass(BundleInterface::class);
        $bundle->method('getPath')->willReturn($this->bundleDir->url());
        $bundle->method('getName')->willReturn(self::BUNDLE_NAME);

        return $bundle;
    }
}
