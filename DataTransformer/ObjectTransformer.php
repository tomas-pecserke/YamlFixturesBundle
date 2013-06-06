<?php
namespace Pecserke\YamlFixturesBundle\DataTransformer;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ObjectTransformer implements ObjectTransformerInterface
{
    /**
     * Transforms an associative array into an object of speficied class.
     *
     * For each key of the array public property is set if exists,
     * otherwise the setter or add method is called if exists.
     * If none of above applies, an InvalidArgumentException is thrown.
     *
     * @param array $data
     * @param string $className
     * @throws InvalidArgumentException
     * @throws Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @return mixed
     */
    public function transform(array $data, $className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("class '$className' does not exist");
        }

        $object = new $className();

        $accessor = PropertyAccess::getPropertyAccessor();
        foreach ($data as $property => $value) {
            $accessor->setValue($object, (string) $property, $value);
        }

        return $object;
    }
}
