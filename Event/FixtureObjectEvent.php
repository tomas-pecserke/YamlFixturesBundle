<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FixtureObjectEvent extends Event {
    /**
     * @var object
     */
    protected $object;

    /**
     * @var string
     */
    protected $referenceName;

    /**
     * @param object $object
     * @param string $referenceName
     */
    public function __construct($object, $referenceName) {
        $this->object = $object;
        $this->referenceName = $referenceName;
    }

    /**
     * @return object
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @return string
     */
    public function getReferenceName() {
        return $this->referenceName;
    }
}
