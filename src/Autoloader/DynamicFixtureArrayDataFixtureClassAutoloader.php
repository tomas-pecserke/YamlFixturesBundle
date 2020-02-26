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

use Pecserke\YamlFixturesBundle\Fixture\DependentFixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Fixture\DependentOrderedFixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Fixture\FixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Fixture\OrderedFixtureArrayDataFixture;

final class DynamicFixtureArrayDataFixtureClassAutoloader extends AbstractAutoloader {
    /**
     * @var array
     */
    private $registered = [];

    public function loadClass(string $className): void {
        if (!array_key_exists($className, $this->registered)) {
            return;
        }

        $classProperties = $this->registered[$className];
        if ($classProperties['ordered']) {
            $newClass = $classProperties['dependent']
                ? new class extends DependentOrderedFixtureArrayDataFixture {}
                : new class extends OrderedFixtureArrayDataFixture {};
        } else {
            $newClass = $classProperties['dependent']
                ? new class extends DependentFixtureArrayDataFixture {}
                : new class extends FixtureArrayDataFixture {};
        }
        $newClassName = get_class($newClass);
        class_alias($newClassName, $className);
    }

    public function registerFixtureClass(string $name, bool $ordered, bool $dependent): void {
        $this->registered[$name] = ['ordered' => $ordered, 'dependent' => $dependent];
    }
}
