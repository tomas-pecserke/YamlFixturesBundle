<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Transformer;

use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\Stubs\ExampleObject;
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

    public function testTransform(): void {
        $data = ['publicProperty' => 'value1', 'privatePropertyWithSetMethod' => 'value2'];
        $object = $this->transformer->transform($data, ExampleObject::class);

        $this->assertInstanceOf(ExampleObject::class, $object);
        $this->assertEquals($data['publicProperty'], $object->publicProperty);
        $this->assertEquals($data['privatePropertyWithSetMethod'], $object->getPrivatePropertyWithSetMethod());
    }

    public function testTransformPrivatePropertyWithoutSetter(): void {
        $this->expectException(NoSuchPropertyException::class);

        $this->transformer->transform(['privateProperty' => 'value'], ExampleObject::class);
    }

    public function testTransformNonExistProperty(): void {
        $this->expectException(NoSuchPropertyException::class);

        $this->transformer->transform(['nonExistProperty' => 'value'], ExampleObject::class);
    }

    public function testTransformPrivateNonExistClass(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'Pecserke\YamlFixturesBundle\Stubs\NonExistObject' does not exist");

        $this->transformer->transform([], 'Pecserke\YamlFixturesBundle\Stubs\NonExistObject');
    }
}
