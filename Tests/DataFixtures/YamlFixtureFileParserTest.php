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
        $fixtures = $parser->parse(array($file));

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
        $parser->parse(array(null));
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
        if (strpos(phpversion(), '5.3') === 0) {
            $this->markTestSkipped();
        }

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
    public function testParseClassNotExists()
    {
        $file = vfsStream::url('testDir/testFile.yml');
        file_put_contents($file, 'ThisClassDoesNotExists: []');
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^order must be int, '.*' given in file '.*'$/
     */
    public function testParseClassOrderNotInteger()
    {
        $file = vfsStream::url('testDir/testFile.yml');
        file_put_contents(
            $file,
            'Pecserke\\YamlFixturesBundle\\Tests\\Fixtures\\DataTransformer\\ExampleObject: { order: not int }'
        );
        $parser = new YamlFixtureFileParser();
        $parser->parse(array($file));
    }

    public function testParseUnorderedAfterOrdered()
    {
        $file = vfsStream::url('testDir/1.yml');
        file_put_contents(
            $file,
            <<<EOT
Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject:
    data:
        example.object.0:
            publicProperty: value0
EOT
        );
        $file2 = vfsStream::url('testDir/2.yml');
        file_put_contents(
            $file2,
            <<<EOT
Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject:
    order: 3
    data:
        example.object.1:
            publicProperty: value0
EOT
        );
        $parser = new YamlFixtureFileParser();
        $fixtures = $parser->parse(array($file, $file2));

        $this->assertArrayHasKey(3, $fixtures);
        $this->assertArrayHasKey(4, $fixtures);
        $this->assertArrayHasKey('example.object.0', $fixtures[4][0]['data']);
    }
}
