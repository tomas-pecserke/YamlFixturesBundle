<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Stubs;

class ExampleObject {
    public $publicProperty;
    private $privateProperty;
    private $privatePropertyWithSetMethod;
    private $privatePropertyWithAddMethod;

    public function getPrivatePropertyWithSetMethod() {
        return $this->privatePropertyWithSetMethod;
    }

    public function setPrivatePropertyWithSetMethod($privatePropertyWithSetMethod) {
        $this->privatePropertyWithSetMethod = $privatePropertyWithSetMethod;
    }

    public function getPrivatePropertyWithAddMethod() {
        return $this->privatePropertyWithAddMethod;
    }

    public function addPrivatePropertyWithAddMethod($privatePropertyWithAddMethod) {
        $this->privatePropertyWithAddMethod = $privatePropertyWithAddMethod;
    }

    protected function getPrivateProperty() {
        return $this->privateProperty;
    }
}
