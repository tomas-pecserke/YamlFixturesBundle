<?php
namespace Pecserke\YamlFixturesBundle\DataTransformer;

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
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function transform(array $data, $className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("class '$className' does not exist");
        }

        $object = new $className();
        $publicVariables = get_object_vars($object);

        foreach ($data as $property => $value) {
            $setMethodName = 'set' . ucfirst($property);
            $addMethodName = 'add' . ucfirst($property);

            if (method_exists($object, $setMethodName)) {
                $object->$setMethodName($value);
            } else if (method_exists($object, $addMethodName)) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $arg) {
                    $object->$addMethodName($arg);
                }
            } else if (array_key_exists($property, $publicVariables)) {
                $object->{$property} = $value;
            } else {
                throw new \InvalidArgumentException(
                    "class '$className' does not have public property '$property' nor a setter method for it"
                );
            }
        }

        return $object;
    }
}
