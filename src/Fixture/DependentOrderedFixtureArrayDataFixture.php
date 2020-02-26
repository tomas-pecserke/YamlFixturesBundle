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

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

abstract class DependentOrderedFixtureArrayDataFixture extends FixtureArrayDataFixture implements DependentFixtureInterface, OrderedFixtureInterface {
    use DependentFixtureArrayDataTrait, OrderedFixtureArrayDataTrait;
}
