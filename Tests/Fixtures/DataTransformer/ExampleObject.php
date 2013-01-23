<?php
namespace Publero\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

class ExampleObject
{
    private $id;

    public $publicProperty;

    private $privateProperty;

    private $privatePropertyWithSetMethod;

    private $privatePropertyWithAddMethod;

    public function getId()
    {
        return $this->id;
    }

    public function getPrivatePropertyWithSetMethod()
    {
        return $this->privatePropertyWithSetMethod;
    }

    public function setPrivatePropertyWithSetMethod($privatePropertyWithSetMethod)
    {
        $this->privatePropertyWithSetMethod = $privatePropertyWithSetMethod;
    }

    public function getPrivatePropertyWithAddMethod()
    {
        return $this->privatePropertyWithAddMethod;
    }

    public function addPrivatePropertyWithAddMethod($privatePropertyWithAddMethod)
    {
        $this->privatePropertyWithAddMethod = $privatePropertyWithAddMethod;
    }
}
