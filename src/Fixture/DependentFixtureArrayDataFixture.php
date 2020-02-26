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

abstract class DependentFixtureArrayDataFixture extends FixtureArrayDataFixture implements DependentFixtureInterface {
    use DependentFixtureArrayDataTrait;
}
