<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\ArrayFixturesLoader;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataFixtures\InMemoryObjectManager;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\DateTimeDataTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ArrayFixturesLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var ArrayFixturesLoader
     */
    private $loader;

    /**
     * @var InMemoryObjectManager
     */
    private $manager;

    /**
     * @var ReferenceRepository
     */
    private $referenceRepository;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->set('pecserke_fixtures.object_transformer', new ObjectTransformer());

        $this->manager = new InMemoryObjectManager();
        $this->referenceRepository = new ReferenceRepository($this->manager);

        $this->loader = new ArrayFixturesLoader();
        $this->loader->setReferenceRepository($this->referenceRepository);
        $this->loader->setContainer($this->container);
    }

    public function testLoad()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object.0' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'value2',
                ),
                'example.object.1' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'value5',
                )
            )
        );
        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(count($fixture['data']), $objects);

        foreach ($fixture['data'] as $key => $value) {
            /* @var ExampleObject $object */
            $object = $objects[$key{strlen($key) - 1}];

            $this->assertInstanceOf($fixture['class'], $object);
            $this->assertEquals($value['publicProperty'], $object->publicProperty);
            $this->assertEquals($value['privatePropertyWithSetMethod'], $object->getPrivatePropertyWithSetMethod());
        }
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testLoadPrivatePropertyWithoutSetter()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('privateProperty' => 'value1')
            )
        );
        $this->loader->load($fixture, $this->manager);
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testLoadNotExistProperty()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('notExistProperty' => 'value1')
            )
        );
        $this->loader->load($fixture, $this->manager);
    }

    public function testLoadParameter()
    {
        $parameterName = 'test.param';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => "#$parameterName")
            )
        );

        $this->container->setParameter($parameterName, 'parameter value');
        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertSame($this->container->getParameter($parameterName), $objects[0]->publicProperty);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadParameterNotExist()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => '#parameter_that_dont_exist')
            )
        );
        $this->loader->load($fixture, $this->manager);
    }

    public function testLoadReference()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => "@$referenceName")
            )
        );

        $this->referenceRepository->setReference($referenceName, new ExampleObject());
        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertSame($this->referenceRepository->getReference($referenceName), $objects[0]->publicProperty);
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testLoadReferenceNotExist()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => '@reference_that_does_not_exist')
            )
        );
        $this->loader->load($fixture, $this->manager);
    }

    public function testLoadDataTransformerService()
    {
        $transformerServiceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => array(
                        '@dataTransformer' => "@$transformerServiceName",
                        'date_time' => null
                    )
                )
            )
        );

        $this->container->set($transformerServiceName, new DateTimeDataTransformer());
        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadDataTransformerClassName()
    {
        $transformerClassName = 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\DateTimeDataTransformer';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => array(
                        '@dataTransformer' => $transformerClassName,
                        'date_time' => null
                    )
                )
            )
        );

        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadPostPersist()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => array(
                        '@example.reference',
                        'setPrivatePropertyWithSetMethod',
                        array('@example.object')
                    )
                )
            )
        );

        $example = new ExampleObject();
        $this->referenceRepository->setReference($referenceName, $example);
        $this->loader->load($fixture, $this->manager);

        $this->assertTrue($example->getPrivatePropertyWithSetMethod() instanceof ExampleObject);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage object expected
     */
    public function testLoadPostPersistNotValidCallback()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => array(null, 'setPrivatePropertyWithSetMethod')
                )
            )
        );

        $this->loader->load($fixture, $this->manager);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage array [$object, $method, $params] expected
     */
    public function testLoadPostPersistNotValidCallbackType()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => 'invalid_callback'
                )
            )
        );

        $this->loader->load($fixture, $this->manager);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testLoadPostPersistNotValidCallbackMethod()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => array(
                        '@'. $referenceName,
                        'setPrivateProperty',
                        array('@example.object')
                    )
                )
            )
        );

        $this->referenceRepository->setReference($referenceName, new ExampleObject());
        $this->loader->load($fixture, $this->manager);
    }

    public function testLoadCustomObjectTransformerClassName()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'transformer' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ObjectTransformer',
            'data' => array(
                'example.object' => array('publicProperty' => 'now')
            )
        );

        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadCustomObjectTransformerService()
    {
        $serviceName = 'object_transformer.all_date_time';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'transformer' => "@$serviceName",
            'data' => array(
                'example.object' => array('publicProperty' => 'now')
            )
        );

        $this->container->set(
            $serviceName,
            new \Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ObjectTransformer()
        );
        $this->loader->load($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadFixtureEqualCondition()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object.0' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'value2',
                ),
                'example.object.1' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'value5',
                )
            )
        );
        $this->loader->load($fixture, $this->manager);

        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'equal_condition' => array('publicProperty'),
            'data' => array(
                'example.object.2' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'different_value2',
                ),
                'example.object.3' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'different_value5',
                )
            )
        );
        $this->loader->load($fixture, $this->manager);

        $this->assertCount(2, $this->manager->all());
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testLoadEqualConditionPropertyWithoutAccess()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'equal_condition' => array('privateProperty'),
            'data' => array(
                'example.object.2' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'value2',
                ),
                'example.object.3' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'value5',
                )
            )
        );
        $this->loader->load($fixture, $this->manager);
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testLoadEqualConditionNotExistProperty()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'equal_condition' => array('notExistProperty'),
            'data' => array(
                'example.object.2' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'value2',
                ),
                'example.object.3' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'value5',
                )
            )
        );
        $this->loader->load($fixture, $this->manager);
    }
}
