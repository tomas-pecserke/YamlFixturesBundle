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

use Pecserke\YamlFixturesBundle\DataFixtures\Locator\FilesystemLocator;
use Symfony\Component\Filesystem\Filesystem;

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
        $this->rootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(rand()) . DIRECTORY_SEPARATOR;
        mkdir($this->rootDir);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->rootDir);
    }

    public function testFind()
    {
        mkdir($this->rootDir . '2/1', 0777, true);
        mkdir($this->rootDir . '2/3', 0777, true);

        // cannot use touch, because it's only supported with wrappers (such as vfs uses) since PHP 5.4
        file_put_contents($this->rootDir . '2/3/1.yml', '');
        file_put_contents($this->rootDir . '2/1/2.yml', '');
        file_put_contents($this->rootDir . '1.yml', '');
        file_put_contents($this->rootDir . '2/2.yml', '');
        file_put_contents($this->rootDir . '3.yml', '');
        file_put_contents($this->rootDir . '2/1/1.yml', '');

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
