<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Transformer;

interface PropertyValueTransformerInterface {
    /**
     * Transforms a property value to another type.
     *
     * @param mixed $value
     * @return mixed
     */
    public function transform($value);
}
