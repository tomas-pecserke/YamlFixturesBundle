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

use Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface;

class DateTimeDataTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return new \DateTime($data['date_time']);
    }
}
