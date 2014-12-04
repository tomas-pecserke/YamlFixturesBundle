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

use Pecserke\YamlFixturesBundle\DataFixtures\Locator\BundleResourcesLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleResourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $bundle;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|KernelInterface
     */
    protected $kernel;

    /**
     * @var BundleResourcesLocator
     */
    protected $locator;

    protected $rootDir;
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
        $this->rootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(rand());

        $appRoot = $this->rootDir . '/app';
        $this->appBundleFixturesDir = $appRoot . '/Resources/TestBundle/fixtures/';
        mkdir($this->appBundleFixturesDir, 0777, true);
        $this->kernel->expects($this->any())->method('getRootDir')->willReturn($appRoot);

        $bundleDir = $this->rootDir . '/bundles/TestBundle';
        $this->bundleFixturesDir = $bundleDir . '/Resources/fixtures/';
        mkdir($this->bundleFixturesDir, 0777, true);
        $this->bundle->expects($this->any())->method('getPath')->willReturn($bundleDir);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->rootDir);
    }


    public function testFind()
    {
        mkdir($this->bundleFixturesDir . '3');

        // cannot use touch, because it's only supported with wrappers (such as vfs uses) since PHP 5.4
        file_put_contents($this->bundleFixturesDir . '3/3.yml', '');
        file_put_contents($this->bundleFixturesDir . '2.yml', '');
        file_put_contents($this->appBundleFixturesDir . '2.yml', '');
        file_put_contents($this->appBundleFixturesDir . '4.yml', '');
        file_put_contents($this->bundleFixturesDir . '1.yml', '');

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
