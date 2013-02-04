<?php
namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer as BaseObjectTransformer;

class ObjectTransformer extends BaseObjectTransformer
{
    /**
     * Transforms an associative array into an object of speficied class.
     *
     * Additionally all the properties are transformed into DateTime objects.
     *
     * @param array $data
     * @param string $className
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function transform(array $data, $className)
    {
        foreach ($data as &$value) {
            $value = new \DateTime($value);
        }

        return parent::transform($data, $className);
    }
}
