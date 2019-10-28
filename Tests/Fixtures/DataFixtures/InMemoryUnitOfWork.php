<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Common\PropertyChangedListener;

class InMemoryUnitOfWork implements PropertyChangedListener {
    public function propertyChanged($sender, $propertyName, $oldValue, $newValue): void {
        // do nothing
    }

    public function isInIdentityMap(): bool {
        return false;
    }
}
