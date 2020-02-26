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

trait OrderedFixtureArrayDataTrait {
    /**
     * @var int
     */
    private $order = 0;

    public function getOrder(): int {
        return $this->order;
    }

    public function setOrder(int $order): void {
        $this->order = $order;
    }
}
