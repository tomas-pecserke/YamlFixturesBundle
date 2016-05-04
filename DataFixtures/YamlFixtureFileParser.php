<?php
namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses YaML file(s) into an array.
 */
class YamlFixtureFileParser
{
    /**
     * Parses YaML files and returns sorted array of fixtures.
     *
     * @param array $fixtureFiles
     * @throws \InvalidArgumentException
     * @throws InvalidFixturesException
     * @return array
     */
    public function parse(array $fixtureFiles)
    {
        $mapFn = function ($filename) {
            return Yaml::parse($filename);
        };
        $fixturesData = array_combine($fixtureFiles, array_map($mapFn, $fixtureFiles));

        $sorted = array();
        $unsorted = array();

        foreach ($fixturesData as $file => $fixtures) {
            if (empty($fixtures)) {
                throw InvalidFixturesException::emptyData($file);
            }
            if (!is_array($fixtures)) {
                throw InvalidFixturesException::mustBeArray($file, $fixtures);
            }
            foreach ($fixtures as $class => $fixture) {
                if (!class_exists($class)) {
                    throw new \InvalidArgumentException("class '$class' doesn't exist in file '$file'");
                }

                $fixture['file'] = $file;
                $fixture['class'] = $class;

                if (isset($fixture['order'])) {
                    $order = $fixture['order'];
                    unset($fixture['order']);
                    if (!is_int($order) && !ctype_digit($order)) {
                        $error = "order must be int, '$order' given in file '$file' fixture '$class'";
                        throw new \InvalidArgumentException($error);
                    }
                    $sorted[$order][] = $fixture;
                } else {
                    $unsorted[] = $fixture;
                }
            }
        }

        ksort($sorted, SORT_NUMERIC);
        if (!empty($unsorted)) {
            $sorted[] = $unsorted;
        }

        return $sorted;
    }
}
