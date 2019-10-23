<?php

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository as BaseReferenceRepository;

class ReferenceRepository extends BaseReferenceRepository {
    private $references = array();

    public function __construct() {
    }

    public function getReference($name) {
        if (isset($this->references[$name])) {
            return $this->references[$name];
        }
        $identities = $this->getIdentities();
        if (isset($identities[$name])) {
            return $identities[$name];
        }

        throw new \InvalidArgumentException("reference '$name' doesn't exist");
    }

    public function setReference($name, $reference) {
        $this->references[$name] = $reference;
    }

    public function addReference($name, $object) {
        if (isset($this->references[$name])) {
            throw new \BadMethodCallException(
                "Reference to: ({$name}) already exists, use method setReference in order to override it"
            );
        }
        $this->setReference($name, $object);
    }

    public function getReferences() {
        return $this->references;
    }
}
