<?php
namespace Pecserke\YamlFixturesBundle\DataFixtures\Exception;

class InvalidFixturesException extends \InvalidArgumentException
{
    /**
     * @param string $file
     * @return InvalidFixturesException
     */
    static public function emptyData($file)
    {
        return new InvalidFixturesException("no fixture configuration found in file '$file', fix this by adding fixture configuration to file or by removing the file");
    }

    /**
     * @param string $file
     * @param mixed $fixtures
     * @return InvalidFixturesException
     */
    static public function mustBeArray($file, $fixtures)
    {
        return new InvalidFixturesException("fixtures in file '$file', must be defined as array. Data given: " . print_r($fixtures, true));
    }

    /**
     * @param mixed $filename
     * @return InvalidFixturesException
     */
    static public function invalidFilenameType($filename)
    {
        return new InvalidFixturesException("filename expected to be string, got '" . gettype($filename) . "'");
    }

    /**
     * @param string $filename
     * @return InvalidFixturesException
     */
    public static function fileDoesNotExist($filename) {
        return new InvalidFixturesException("file '$filename' does not exist");
    }

    /**
     * @param string $filename
     * @return InvalidFixturesException
     */
    public static function fileNotReadable($filename) {
        return new InvalidFixturesException("cannot read file '$filename'");
    }
}
