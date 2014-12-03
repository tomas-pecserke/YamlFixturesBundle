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

use Doctrine\Common\DataFixtures\ReferenceRepository as BaseReferenceRepository;

class ReferenceRepository extends BaseReferenceRepository
{
    private $references = array();

    public function __construct()
    {
    }

    /**
     * @param string $name
     * @return object
     *
     * @throws \OutOfBoundsException
     */
    public function getReference($name)
    {
        if (isset($this->references[$name])) {
            return $this->references[$name];
        }
        $identities = $this->getIdentities();
        if (isset($identities[$name])) {
            return $identities[$name];
        }

        throw new \OutOfBoundsException("reference '$name' doesn't exist");
    }

    public function setReference($name, $reference)
    {
        $this->references[$name] = $reference;
    }

    public function addReference($name, $object)
    {
        if (isset($this->references[$name])) {
            throw new \BadMethodCallException(
                "Reference to: ({$name}) already exists, use method setReference in order to override it"
            );
        }
        $this->setReference($name, $object);
    }
}
