<?php
namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer;

class ExampleObject
{
    private $id;

    public $publicProperty;

    private $privateProperty;

    private $privatePropertyWithSetMethod;

    private $privatePropertyWithAddMethod;

    /**
     * @var ExampleObject
     */
    private $privateSelfReferenceParentProperty;

    /**
     * @var ExampleObject[]
     */
    private $privateSelfReferenceChildrenProperty;

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

    protected function getPrivateProperty()
    {
        return $this->privateProperty;
    }

    /**
     * @return ExampleObject[]
     */
    public function getPrivateSelfReferenceChildrenProperty()
    {
        return $this->privateSelfReferenceChildrenProperty;
    }

    /**
     * @param ExampleObject[] $privateSelfReferenceChildrenProperty
     */
    public function setPrivateSelfReferenceChildrenProperty($privateSelfReferenceChildrenProperty)
    {
        $this->privateSelfReferenceChildrenProperty = $privateSelfReferenceChildrenProperty;
    }

    /**
     * @return ExampleObject
     */
    public function getPrivateSelfReferenceParentProperty()
    {
        return $this->privateSelfReferenceParentProperty;
    }

    /**
     * @param ExampleObject $privateSelfReferenceParentProperty
     */
    public function setPrivateSelfReferenceParentProperty($privateSelfReferenceParentProperty)
    {
        $this->privateSelfReferenceParentProperty = $privateSelfReferenceParentProperty;
    }
}
