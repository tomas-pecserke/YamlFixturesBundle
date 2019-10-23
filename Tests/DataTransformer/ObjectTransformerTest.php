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

use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class ObjectTransformerTest extends TestCase {
    /**
     * @var ObjectTransformer
     */
    private $transformer;

    protected function setUp(): void {
        $this->transformer = new ObjectTransformer();
    }

    public function testTransform() {
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

    public function testTransformPrivatePropertyWithoutSetter() {
        $this->expectException(NoSuchPropertyException::class);

        $this->transformer->transform(
            array('privateProperty' => 'value'),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject'
        );
    }

    public function testTransformNonExistProperty() {
        $this->expectException(NoSuchPropertyException::class);

        $this->transformer->transform(
            array('nonExistProperty' => 'value'),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject'
        );
    }

    public function testTransformPrivateNonExistClass() {
        $this->expectException(InvalidArgumentException::class);

        $this->transformer->transform(
            array(),
            'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\NonExistObject'
        );
    }
}
