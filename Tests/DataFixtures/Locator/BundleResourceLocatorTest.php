<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures\Locator;

use org\bovigo\vfs\vfsStream;
use Pecserke\YamlFixturesBundle\DataFixtures\Locator\BundleResourcesLocator;

class BundleResourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $bundle;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $kernel;

    /**
     * @var BundleResourcesLocator
     */
    protected $locator;

    protected $appBundleFixturesDir;
    protected $bundleFixturesDir;

    protected function setUp()
    {
        $this->kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->setMethods(array('getBundle', 'getRootDir'))
            ->getMockForAbstractClass()
        ;
        $this->bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')
            ->setMethods(array('getPath'))
            ->getMockForAbstractClass()
        ;
        $this->kernel
            ->expects($this->any())
            ->method('getBundle')
            ->with($this->equalTo('TestBundle'))
            ->willReturn($this->bundle)
        ;

        $this->locator = new BundleResourcesLocator($this->kernel);

        $rootDir = 'bundle_resource_locator_test';
        $root = vfsStream::setup($rootDir);
        $rootDir = $root->url($rootDir);

        $appRoot = $rootDir . '/app';
        $this->appBundleFixturesDir = $appRoot . '/Resources/TestBundle/fixtures/';
        mkdir($this->appBundleFixturesDir, 0777, true);
        $this->kernel->expects($this->any())->method('getRootDir')->willReturn($appRoot);

        $bundleDir = $rootDir . '/bundles/TestBundle';
        $this->bundleFixturesDir = $bundleDir . '/Resources/fixtures/';
        mkdir($this->bundleFixturesDir, 0777, true);
        $this->bundle->expects($this->any())->method('getPath')->willReturn($bundleDir);
    }

    public function testFind()
    {
        mkdir($this->bundleFixturesDir . '3');

        touch($this->bundleFixturesDir . '3/3.yml');
        touch($this->bundleFixturesDir . '2.yml');
        touch($this->appBundleFixturesDir . '2.yml');
        touch($this->appBundleFixturesDir . '4.yml');
        touch($this->bundleFixturesDir . '1.yml');

        $this->assertEquals(
            array(
                $this->bundleFixturesDir . '1.yml',
                $this->appBundleFixturesDir . '2.yml', // definition placed in app resources dir has priority
                $this->bundleFixturesDir . '3/3.yml',
                $this->appBundleFixturesDir . '4.yml'
            ),
            $this->locator->find('TestBundle')
        );
    }

    public function testFindEmpty()
    {
        $this->assertEmpty($this->locator->find('TestBundle'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindBundleNotRegistered()
    {
        $this->kernel
            ->expects($this->once())
            ->method('getBundle')
            ->with($this->equalTo('NonExistentBundle'))
            ->will($this->throwException(new \InvalidArgumentException()))
        ;

        $this->assertEmpty($this->locator->find('NonExistentBundle'));
    }

    public function testFindNoAppNorBundleResourceDir()
    {
        rmdir($this->appBundleFixturesDir);
        rmdir($this->bundleFixturesDir);

        $this->assertEmpty($this->locator->find('TestBundle'));
    }
}