<?php
namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;

class YamlFixtureFileParserTest extends \PHPUnit_Framework_TestCase
{
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
     * @expectedExceptionMessage file 'non_existent_file' does not exist
     */
    public function testParseThrowsInvalidFixturesOnFileDoesNotExist()
    {
        $parser = new YamlFixtureFileParser();
        $parser->parse(array(
            'non_existent_file'
        ));
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessage cannot read file 'not_readable_file'
     */
    public function testParseThrowsInvalidFixturesOnFileNotReadable()
    {
        try {
            file_put_contents('not_readable_file', '');
            chmod('not_readable_file', 0222);
            $parser = new YamlFixtureFileParser();
            $parser->parse(array('not_readable_file'));
            unlink('not_readable_file');
        } catch (InvalidFixturesException $e) {
            unlink('not_readable_file');
            throw $e;
        }
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessage no fixture configuration found in file 'empty_file', fix this by adding fixture configuration to file or by removing the file
     */
    public function testParseThrowsInvalidFixturesOnEmptyFile()
    {
        try {
            file_put_contents('empty_file', '');
            $parser = new YamlFixtureFileParser();
            $parser->parse(array('empty_file'));
            unlink('empty_file');
        } catch (InvalidFixturesException $e) {
            unlink('empty_file');
            throw $e;
        }
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     * @expectedExceptionMessageRegExp  /^fixtures in file 'incorrect_data', must be defined as array. Data given: /
     */
    public function testParseThrowsInvalidFixturesOnNotArrayFixtures()
    {
        try {
            file_put_contents('incorrect_data', '1');
            $parser = new YamlFixtureFileParser();
            $parser->parse(array('incorrect_data'));
            unlink('incorrect_data');
        } catch (InvalidFixturesException $e) {
            unlink('incorrect_data');
            throw $e;
        }
    }
}
