<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;
use PHPUnit\Framework\TestCase;

class YamlFixtureFileParserTest extends TestCase {
    /**
     * @throws vfsStreamException
     */
    public function setUp(): void {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testParse() {
        $file = __DIR__ . '/../Fixtures/AcmeDemoBundle/Resources/fixtures/example_1.yml';
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array($file));

        $this->assertNotEmpty($fixtures);
        $this->assertArrayHasKey(3, $fixtures, "Expected fixtures of order 3");
        $fixtures = $fixtures[3];
        $this->assertCount(1, $fixtures);
        $fixture = array_pop($fixtures);
        $this->assertEquals($file, $fixture['file']);
    }

    public function testParseNoFixtures() {
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array());

        $this->assertEmpty($fixtures);
    }

    public function testParseThrowsInvalidFixturesOnFilenameNotString() {
        $this->expectException(InvalidFixturesException::class);
        $this->expectExceptionMessage('filename expected to be string, got \'NULL\'');

        $parser = new YamlFixtureFileParser();
        $parser->parse(array(null));
    }

    public function testParseThrowsInvalidFixturesOnFileDoesNotExist() {
        $this->expectException(InvalidFixturesException::class);
        $this->expectExceptionMessageMatches('/^file \'.*\' does not exist$/');

        $file = vfsStream::url('testDir/non_existent_file');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseThrowsInvalidFixturesOnFileNotReadable() {
        $this->expectException(InvalidFixturesException::class);
        $this->expectExceptionMessageMatches('/^cannot read file \'.*\'$/');

        if (strpos(phpversion(), '5.3') === 0) {
            $this->markTestSkipped();
        }

        $file = vfsStream::url('testDir/not_readable_file');
        file_put_contents($file, '');
        chmod($file, 0222);
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseThrowsInvalidFixturesOnEmptyFile() {
        $this->expectException(InvalidFixturesException::class);
        $this->expectExceptionMessageMatches('/^no fixture configuration found in file \'.*\', fix this by adding fixture configuration to file or by removing the file$/');

        $file = vfsStream::url('testDir/empty_file');
        file_put_contents($file, '');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseThrowsInvalidFixturesOnNotArrayFixtures() {
        $this->expectException(InvalidFixturesException::class);
        $this->expectExceptionMessageMatches('/^fixtures in file \'.*\', must be defined as array. Data given: /');

        $file = vfsStream::url('testDir/incorrect_data');
        file_put_contents($file, '1');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseClassNotExists() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^class \'.*\' doesn\'t exist in file \'.*\'$/');

        $file = vfsStream::url('testDir/testFile.yml');
        file_put_contents($file, 'ThisClassDoesNotExists: []');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseClassOrderNotInteger() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^order must be int, \'.*\' given in file \'.*\'$/');

        $file = vfsStream::url('testDir/testFile.yml');
        file_put_contents(
            $file,
            'Pecserke\\YamlFixturesBundle\\Stubs\\ExampleObject: { order: not int }'
        );
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseUnorderedAfterOrdered() {
        $file = vfsStream::url('testDir/1.yml');
        file_put_contents($file, <<< YaML
Pecserke\YamlFixturesBundle\Stubs\ExampleObject:
    data:
        example.object.0:
            publicProperty: value0
YaML
        );
        $file2 = vfsStream::url('testDir/2.yml');
        file_put_contents($file2, <<< YaML
Pecserke\YamlFixturesBundle\Stubs\ExampleObject:
    order: 3
    data:
        example.object.1:
            publicProperty: value0
YaML
        );
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array($file, $file2));

        $this->assertArrayHasKey(3, $fixtures);
        $this->assertArrayHasKey(4, $fixtures);
        $this->assertArrayHasKey('example.object.0', $fixtures[4][0]['data']);
    }
}
