<?php
namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;

class YamlFixtureFileParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     */
    public function testParseThrowsInvalidFixturesOnEmptyData()
    {
        $parser = new YamlFixtureFileParser();
        $parser->parse(array());
    }

    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException
     */
    public function testParseThrowsInvalidFixturesOnNotArrayFixtures()
    {
        $parser = new YamlFixtureFileParser();
        $parser->parse(array(
            array('file' => 'incorrect_data')
        ));
    }
}
