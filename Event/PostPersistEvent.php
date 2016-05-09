<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Event;

class PostPersistEvent extends FixtureObjectEvent
{
    /**
     * @var array
     */
    protected $postPersist;

    /**
     * @param object $object
     * @param string $referenceName
     * @param array $postPersist
     */
    public function __construct($object, $referenceName, $postPersist)
    {
        parent::__construct($object, $referenceName);
        $this->postPersist = $postPersist;
    }

    /**
     * @return array
     */
    public function getPostPersist()
    {
        return $this->postPersist;
    }
}
