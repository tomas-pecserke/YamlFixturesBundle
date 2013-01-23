<?php
namespace Publero\YamlFixturesBundle\DataTransformer;

use Doctrine\Common\DataFixtures\ReferenceRepository;

interface ObjectTransformerInterface
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
    public function transform(array $data, $className);
}
