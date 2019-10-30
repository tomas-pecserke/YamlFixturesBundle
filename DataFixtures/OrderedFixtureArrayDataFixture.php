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

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class OrderedFixtureArrayDataFixture extends FixtureArrayDataFixture implements OrderedFixtureInterface {
    /**
     * @var int
     */
    private $order;

    public function __construct(FixtureArrayDataLoaderInterface $loader, array $fixtureData, int $order) {
        parent::__construct($loader, $fixtureData);
        $this->order = $order;
    }

    public function getOrder(): int {
        return $this->order;
    }
}
