<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixturesLocator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class YamlFixturesLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testFindInDirectory()
    {
        $dir = vfsStream::url('testDir');
        file_put_contents($dir . '/1.yml', '');
        mkdir($dir . '/sub');
        file_put_contents($dir . '/sub/2.yml', '');
        file_put_contents($dir . '/sub/1.yml', '');

        /* @var Kernel $kernel */
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $locator = new YamlFixturesLocator($kernel);
        $files = $locator->findInDirectory($dir);

        $expected = array(
            'vfs://testDir/1.yml' => 'vfs://testDir/1.yml',
            'vfs://testDir/sub/1.yml' => 'vfs://testDir/sub/1.yml',
            'vfs://testDir/sub/2.yml' => 'vfs://testDir/sub/2.yml'
        );
        $this->assertEquals($expected, $files);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^'.*' isn't a directory or can noŧ read it$/
     */
    public function testFindInDirectoryNotDirectory()
    {
        $dir = vfsStream::url('testDir');
        file_put_contents($dir . '/1', '');

        /* @var Kernel $kernel */
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $locator = new YamlFixturesLocator($kernel);
        $locator->findInDirectory($dir . '/1');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^'.*' isn't a directory or can noŧ read it$/
     */
    public function testFindInDirectoryNotExists()
    {
        $dir = vfsStream::url('testDir');

        /* @var Kernel $kernel */
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $locator = new YamlFixturesLocator($kernel);
        $locator->findInDirectory($dir . '/1');
    }

    public function testFindInBundle()
    {
        /* @var Kernel $kernel
         * @var BundleInterface $bundle */
        list($kernel, $bundle) = $this->getKernelAndBundleMock();
        $locator = new YamlFixturesLocator($kernel);
        $files = $locator->findInBundle($bundle->getName());

        $this->assertEmpty($files);
    }

    public function testFindInBundleInBundleDir()
    {
        /* @var Kernel $kernel
         * @var BundleInterface $bundle */
        list($kernel, $bundle) = $this->getKernelAndBundleMock();
        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        mkdir($bundleResourceDir, 0777, true);
        file_put_contents($bundleResourceDir . '/1.yml', '');
        mkdir($bundleResourceDir . '/sub');
        file_put_contents($bundleResourceDir . '/sub/1.yml', '');
        file_put_contents($bundleResourceDir . '/sub/2.yml', '');

        $locator = new YamlFixturesLocator($kernel);
        $files = $locator->findInBundle($bundle->getName());

        $this->assertCount(3, $files);
        $this->assertContains('vfs://testDir/bundle/Resources/fixtures/1.yml', $files);
        $this->assertContains('vfs://testDir/bundle/Resources/fixtures/sub/1.yml', $files);
        $this->assertContains('vfs://testDir/bundle/Resources/fixtures/sub/2.yml', $files);
    }

    public function testFindInBundleInBundleRootDir()
    {
        /* @var Kernel $kernel
         * @var BundleInterface $bundle */
        list($kernel, $bundle) = $this->getKernelAndBundleMock();
        $rootResourceDir = $kernel->getRootDir() . '/Resources/' . $bundle->getName() . '/fixtures';
        mkdir($rootResourceDir, 0777, true);
        file_put_contents($rootResourceDir . '/1.yml', '');
        mkdir($rootResourceDir . '/sub');
        file_put_contents($rootResourceDir . '/sub/1.yml', '');
        file_put_contents($rootResourceDir . '/sub/2.yml', '');

        $locator = new YamlFixturesLocator($kernel);
        $files = $locator->findInBundle($bundle->getName());

        $this->assertCount(3, $files);
        $this->assertContains('vfs://testDir/root/Resources/test/fixtures/1.yml', $files);
        $this->assertContains('vfs://testDir/root/Resources/test/fixtures/sub/1.yml', $files);
        $this->assertContains('vfs://testDir/root/Resources/test/fixtures/sub/2.yml', $files);
    }

    public function testFindInBundleInBundleOverride()
    {
        /* @var Kernel $kernel
         * @var BundleInterface $bundle */
        list($kernel, $bundle) = $this->getKernelAndBundleMock();
        $bundleResourceDir = $bundle->getPath() . '/Resources/fixtures';
        mkdir($bundleResourceDir, 0777, true);
        $rootResourceDir = $kernel->getRootDir() . '/Resources/' . $bundle->getName() . '/fixtures';
        mkdir($rootResourceDir, 0777, true);
        file_put_contents($bundleResourceDir . '/1.yml', '');
        file_put_contents($rootResourceDir . '/1.yml', '');
        mkdir($bundleResourceDir . '/sub');
        mkdir($rootResourceDir . '/sub');
        file_put_contents($bundleResourceDir . '/sub/1.yml', '');
        file_put_contents($rootResourceDir . '/sub/1.yml', '');
        file_put_contents($bundleResourceDir . '/sub/2.yml', '');

        $locator = new YamlFixturesLocator($kernel);
        $files = $locator->findInBundle($bundle->getName());

        $this->assertCount(3, $files);
        $this->assertContains('vfs://testDir/root/Resources/test/fixtures/1.yml', $files);
        $this->assertContains('vfs://testDir/root/Resources/test/fixtures/sub/1.yml', $files);
        $this->assertContains('vfs://testDir/bundle/Resources/fixtures/sub/2.yml', $files);
    }

    private function getKernelAndBundleMock()
    {
        $dir = vfsStream::url('testDir');
        $rootDir = $dir . '/root';
        $bundleDir = $dir . '/bundle';

        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->setMethods(array('getBundle', 'getRootDir'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')
            ->setMethods(array('getPath'))
            ->getMockForAbstractClass()
        ;
        $kernel->method('getBundle')->willReturn($bundle);
        $kernel->method('getRootDir')->willReturn($rootDir);
        $bundle->method('getPath')->willReturn($bundleDir);
        $bundle->method('getName')->willReturn('test');

        return array($kernel, $bundle);
    }
}
