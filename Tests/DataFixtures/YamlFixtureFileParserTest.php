<?php
namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;

class YamlFixtureFileParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Pecserke\YamlFixturesBundle\DataFixtures\Exception\ConfigurationNotFoundException
     */
    public function testParseThrowsConfigurationNotFundExceptionIfYamlFileIsEmpty()
    {
        $parser = new YamlFixtureFileParser();
        $parser->parse(['']);
    }
}
