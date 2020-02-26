<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Fixture;

trait DependentFixtureArrayDataTrait {
    /**
     * @var string[]
     */
    private $dependencies = [];

    /**
     * @return string[]
     */
    public function getDependencies(): array {
        return $this->dependencies;
    }

    /**
     * @param string[] $dependencies
     */
    public function setDependencies(array $dependencies): void {
        $this->dependencies = $dependencies;
    }
}
