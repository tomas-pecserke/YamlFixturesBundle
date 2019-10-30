<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Autoloader;

use Pecserke\YamlFixturesBundle\Fixture\FixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Fixture\OrderedFixtureArrayDataFixture;

final class DynamicFixtureArrayDataFixtureClassAutoloader extends AbstractAutoloader {
    public const CLASS_NAME_BASE = 'Pecserke\\YamlFixturesBundle\\Fixture\\Dynamic\\FixtureArrayDataFixture';
    public const ORDERED_SUFFIX = 'Ordered';

    /**
     * Dynamic fixture array data fixture class name pattern
     *
     * @var string
     */
    private static $pattern;

    public function __construct() {
        if (self::$pattern === null) {
            self::$pattern = sprintf(
                '/^%s(%s)?_[a-zA-Z0-9]+$/',
                str_replace('\\', '\\\\', self::CLASS_NAME_BASE),
                self::ORDERED_SUFFIX
            );
        }
    }

    public function loadClass(string $className): void {
        $matches = [];
        if (!preg_match(self::$pattern, $className, $matches)) {
            return;
        }

        $newClass = empty($matches[1])
            ? new class extends FixtureArrayDataFixture {}
            : new class extends OrderedFixtureArrayDataFixture {};
        $newClassName = get_class($newClass);
        class_alias($newClassName, $className);
    }
}
