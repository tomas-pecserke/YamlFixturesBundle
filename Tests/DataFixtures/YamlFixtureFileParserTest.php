<?php
namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;

class YamlFixtureFileParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testParse()
    {
        $file = __DIR__ . '/../Fixtures/AcmeDemoBundle/Resources/fixtures/example_1.yml';
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array(
            $file
        ));

        $this->assertNotEmpty($fixtures);
        $this->assertArrayHasKey(3, $fixtures, "Expected fixtures of order 3");
        $fixtures = $fixtures[3];
        $this->assertCount(1, $fixtures);
        $fixture = array_pop($fixtures);
        $this->assertEquals($file, $fixture['file']);
    }

    public function testParseNoFixtures()
    {
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array());

        $this->assertEmpty($fixtures);
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessage filename expected to be string, got 'NULL'
     */
    public function testParseThrowsInvalidFixturesOnFilenameNotString()
    {
        $parser = new YamlFixtureFileParser();
        $parser->parse(array(
            null
        ));
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessageRegExp  /^file '.*' does not exist$/
     */
    public function testParseThrowsInvalidFixturesOnFileDoesNotExist()
    {
        $file = vfsStream::url('testDir/non_existent_file');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessageRegExp /^cannot read file '.*'$/
     */
    public function testParseThrowsInvalidFixturesOnFileNotReadable()
    {
        $file = vfsStream::url('testDir/not_readable_file');
        file_put_contents($file, '');
        chmod($file, 0222);
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessageRegExp /^no fixture configuration found in file '.*', fix this by adding fixture configuration to file or by removing the file$/
     */
    public function testParseThrowsInvalidFixturesOnEmptyFile()
    {
        $file = vfsStream::url('testDir/empty_file');
        file_put_contents($file, '');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessageRegExp  /^fixtures in file '.*', must be defined as array. Data given: /
     */
    public function testParseThrowsInvalidFixturesOnNotArrayFixtures()
    {
        $file = vfsStream::url('testDir/incorrect_data');
        file_put_contents($file, '1');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp  /^class '.*' doesn't exist in file '.*'$/
     */
    public function testParseClassNotExists() {
        $file = vfsStream::url('testDir/testFile.yml');
        file_put_contents($file, 'ThisClassDoesNotExists: []');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }
}
