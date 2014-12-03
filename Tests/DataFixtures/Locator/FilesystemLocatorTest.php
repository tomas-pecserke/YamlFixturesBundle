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
use Pecserke\YamlFixturesBundle\DataFixtures\Locator\FilesystemLocator;

class FileSystemLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilesystemLocator
     */
    protected $locator;
    protected $rootDir;

    protected function setUp()
    {
        $this->locator = new FilesystemLocator();

        $rootDir = 'filesystem_locator_test';
        $root = vfsStream::setup($rootDir);
        $this->rootDir = $root->url($rootDir) . '/';
    }

    public function testFind()
    {
        mkdir($this->rootDir . '2/1', 0777, true);
        mkdir($this->rootDir . '2/3', 0777, true);

        touch($this->rootDir . '2/3/1.yml');
        touch($this->rootDir . '2/1/2.yml');
        touch($this->rootDir . '1.yml');
        touch($this->rootDir . '2/2.yml');
        touch($this->rootDir . '3.yml');
        touch($this->rootDir . '2/1/1.yml');

        $this->assertEquals(
            array(
                $this->rootDir . '1.yml',
                $this->rootDir . '2/1/1.yml',
                $this->rootDir . '2/1/2.yml',
                $this->rootDir . '2/2.yml',
                $this->rootDir . '2/3/1.yml',
                $this->rootDir . '3.yml'
            ),
            $this->locator->find($this->rootDir)
        );
    }

    public function testFindEmpty()
    {
        $this->assertEmpty($this->locator->find($this->rootDir));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage is not a readable directory
     */
    public function testFindNotExists()
    {
        $this->assertEmpty($this->locator->find($this->rootDir . 'this_dir_does_not_exist'));
    }
}