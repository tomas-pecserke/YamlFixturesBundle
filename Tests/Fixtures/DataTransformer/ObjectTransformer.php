<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

use DateTime;
use Exception;
use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer as BaseObjectTransformer;

class ObjectTransformer extends BaseObjectTransformer {
    /**
     * Transforms an associative array into an object of specified class.
     *
     * Additionally all the properties are transformed into DateTime objects.
     *
     * @param array $data
     * @param string $className
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function transform(array $data, $className) {
        foreach ($data as &$value) {
            $value = new DateTime($value);
        }

        return parent::transform($data, $className);
    }
}
