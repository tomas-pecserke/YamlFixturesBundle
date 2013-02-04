<?php
namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

use Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface;

class DateTimeDataTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return new \DateTime($data['date_time']);
    }
}
