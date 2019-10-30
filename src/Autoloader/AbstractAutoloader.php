<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Autoloader;

abstract class AbstractAutoloader implements AutoloaderInterface {
    public function __invoke($className = null) {
        if (is_string($className)) {
            $this->loadClass($className);
        }
    }

    abstract protected function loadClass(string $className): void;

    public function register(): void {
        spl_autoload_register($this);
    }

    public function unregister(): void {
        spl_autoload_unregister($this);
    }

    public function isRegistered(): bool {
        return in_array($this, spl_autoload_functions(), true);
    }
}
