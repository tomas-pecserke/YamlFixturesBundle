<?php
namespace Publero\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

use Publero\YamlFixturesBundle\DataTransformer\DataTransformerInterface;

class DateTimeDataTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return new \DateTime($data['date_time']);
    }
}
