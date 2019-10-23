<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\DataFixtures\Exception\InvalidFixturesException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses YaML file(s) into an array.
 */
class YamlFixtureFileParser {
    /**
     * Parses YaML files and returns sorted array of fixtures.
     *
     * @param array $fixtureFiles
     * @return array
     * @throws InvalidFixturesException
     * @throws InvalidArgumentException
     */
    public function parse(array $fixtureFiles) {
        $fixturesData = array();
        foreach ($fixtureFiles as $filename) {
            if (!is_string($filename)) {
                throw InvalidFixturesException::invalidFilenameType($filename);
            }
            if (!file_exists($filename)) {
                throw InvalidFixturesException::fileDoesNotExist($filename);
            }
            if (!is_readable($filename) || ($content = file_get_contents($filename)) === false) {
                throw InvalidFixturesException::fileNotReadable($filename);
            }
            $fixturesData[$filename] = Yaml::parse($content);
        }

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
                    throw new InvalidArgumentException("class '$class' doesn't exist in file '$file'");
                }

                $fixture['file'] = $file;
                $fixture['class'] = $class;

                if (isset($fixture['order'])) {
                    $order = $fixture['order'];
                    unset($fixture['order']);
                    if (!is_int($order) && !ctype_digit($order)) {
                        $error = "order must be int, '$order' given in file '$file' fixture '$class'";
                        throw new InvalidArgumentException($error);
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
