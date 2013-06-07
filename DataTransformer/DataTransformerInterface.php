<?php
namespace Pecserke\YamlFixturesBundle\DataTransformer;

interface DataTransformerInterface
{
    /**
     * Transforms data to another form.
     *
     * @param $data
     * @return mixed
     */
    public function transform($data);
}
