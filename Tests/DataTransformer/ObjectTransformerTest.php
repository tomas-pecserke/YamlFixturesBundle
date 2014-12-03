<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataTransformer;

use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject;

class ObjectTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new ObjectTransformer();
    }

    public function testTransform()
    {
        $data = array(
            'publicProperty' => 'value1',
            'privatePropertyWithSetMethod' => 'value2',
        );

        $object = $this->transformer->transform(
            $data,
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject'
        );

        $this->assertTrue($object instanceof ExampleObject);
        $this->assertEquals($data['publicProperty'], $object->publicProperty);
        $this->assertEquals($data['privatePropertyWithSetMethod'], $object->getPrivatePropertyWithSetMethod());
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testTransformPrivatePropertyWithoutSetter()
    {
        $this->transformer->transform(
            array('privateProperty' => 'value'),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject'
        );
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testTransformNonExistProperty()
    {
        $this->transformer->transform(
            array('nonExistProperty' => 'value'),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTransformPrivateNonExistClass()
    {
        $this->transformer->transform(
            array(),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\NonExistObject'
        );
    }
}
