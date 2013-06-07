<?php
namespace Pecserke\YamlFixturesBundle\DataTransformer;

interface DataTransformerInterface
{
    /**
     * Transforms data to another form.
     *
     * @param mixed $data
     * @return mixed
     */
    public function transform($data);
}
