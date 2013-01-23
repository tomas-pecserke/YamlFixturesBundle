<?php
namespace Publero\YamlFixturesBundle\DataTransformer;

use Doctrine\Common\DataFixtures\ReferenceRepository;

interface DataTransformerInterface
{
    /**
     * Transforms data to another form.
     *
     * @param mixed $class
     * @return mixed
     */
    public function transform($data);
}
