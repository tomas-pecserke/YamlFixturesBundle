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
use Symfony\Component\HttpKernel\Kernel;

class YamlFixturesLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testFindInDirectory() {
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
    public function testFindInDirectoryNotDirectory() {
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
    public function testFindInDirectoryNotExists() {
        $dir = vfsStream::url('testDir');

        /* @var Kernel $kernel */
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $locator = new YamlFixturesLocator($kernel);
        $locator->findInDirectory($dir . '/1');
    }
}
