<?php
namespace Pecserke\YamlFixturesBundle\Tests\DataFixtures;

use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixturesLoader;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataFixtures\InMemoryObjectManager;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\AcmeDemoBundle\AcmeDemoBundle;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\app\TestKernel;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\DateTimeDataTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class YamlFixturesLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var YamlFixturesLoader
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

        $this->loader = new YamlFixturesLoader();
        $this->loader->setReferenceRepository($this->referenceRepository);
        $this->loader->setContainer($this->container);
    }

    public function testLoadFixture()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object.0' => array(
                    'publicProperty' => 'value1',
                    'privatePropertyWithSetMethod' => 'value2',
                    'privatePropertyWithAddMethod' => 'value3'
                ),
                'example.object.1' => array(
                    'publicProperty' => 'value4',
                    'privatePropertyWithSetMethod' => 'value5',
                    'privatePropertyWithAddMethod' => 'value6'
                )
            )
        );
        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(count($fixture['data']), $objects);

        foreach ($fixture['data'] as $key => $value) {
            $object = $objects[$key{strlen($key) - 1}];

            $this->assertInstanceOf($fixture['class'], $object);
            $this->assertEquals($value['publicProperty'], $object->publicProperty);
            $this->assertEquals($value['privatePropertyWithSetMethod'], $object->getPrivatePropertyWithSetMethod());
            $this->assertEquals($value['privatePropertyWithAddMethod'], $object->getPrivatePropertyWithAddMethod());
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixturePrivatePropertyWithoutSetter()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('privateProperty' => 'value1')
            )
        );
        $this->loader->loadFixture($fixture, $this->manager);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixtureNotExistProperty()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('notExistProperty' => 'value1')
            )
        );
        $this->loader->loadFixture($fixture, $this->manager);
    }

    public function testLoadFixtureParameter()
    {
        $parameterName = 'test.param';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => "#$parameterName")
            )
        );

        $this->container->setParameter($parameterName, 'parameter value');
        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertSame($this->container->getParameter($parameterName), $objects[0]->publicProperty);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixtureParameterNotExist()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => '#parameter_that_dont_exist')
            )
        );
        $this->loader->loadFixture($fixture, $this->manager);
    }

    public function testLoadFixtureReference()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => "@$referenceName")
            )
        );

        $this->referenceRepository->setReference($referenceName, new ExampleObject());
        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertSame($this->referenceRepository->getReference($referenceName), $objects[0]->publicProperty);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixtureReferenceNotExist()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array('publicProperty' => '@reference_that_dont_exist')
            )
        );
        $this->loader->loadFixture($fixture, $this->manager);
    }

    public function testLoadFixtureDataTransformerService()
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
        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadFixtureDataTransformerClassName()
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

        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadFixturePostPersist()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => array('@example.reference', 'setPrivatePropertyWithSetMethod')
                )
            )
        );

        $example = new ExampleObject();
        $this->referenceRepository->setReference($referenceName, $example);
        $this->loader->loadFixture($fixture, $this->manager);

        $this->assertTrue($example->getPrivatePropertyWithSetMethod() instanceof ExampleObject);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixturePostPersistNotValidCallback()
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

        $this->loader->loadFixture($fixture, $this->manager);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixturePostPersistNotValidCallbackMetohd()
    {
        $referenceName = 'example.reference';
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'data' => array(
                'example.object' => array(
                    'publicProperty' => 'value',
                    '@postPersist' => array("$referenceName", 'setPrivateProperty')
                )
            )
        );

        $this->referenceRepository->setReference($referenceName, new ExampleObject());
        $this->loader->loadFixture($fixture, $this->manager);
    }

    public function testLoadFixtureCustomObjectTransformerClassName()
    {
        $fixture = array(
            'class' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject',
            'transformer' => 'Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ObjectTransformer',
            'data' => array(
                'example.object' => array('publicProperty' => 'now')
            )
        );

        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadFixtureCustomObjectTransformerService()
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
        $this->loader->loadFixture($fixture, $this->manager);

        $objects = $this->manager->all();

        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->publicProperty instanceof \DateTime);
    }

    public function testLoadFixtureEqualCondition()
    {
        $this->markTestIncomplete();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixtureEqualConditionPropertyWithoutAccess()
    {
        $this->markTestIncomplete();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFixtureEqualConditionNotExistProperty()
    {
        $this->markTestIncomplete();
    }

    public function testGetFixtureFiles()
    {
        /*
         * Even thou Test/Fixtures/AcmeDemoBundle/Resources/fixtures/ contains both files
         * example_1.yml and example_2.yml, the second one is overriden by
         * Test/Fixtures/app/Resources/AcmeDemoBundle/fixtures/example_2.yml.
         */

        $this->container->setParameter('kernel.root_dir', realpath('./Tests/Fixtures/app'));
        $kernel = new TestKernel('test', false);
        $bundle = new AcmeDemoBundle();
        $kernel->setBundles(array($bundle));
        $kernel->boot();
        $this->container->set('kernel', $kernel);

        $expected = array(
            realpath($bundle->getPath() . '/Resources/fixtures/example_1.yml'),
            realpath($kernel->getRootDir() . '/Resources/' . $bundle->getName() . '/fixtures/example_2.yml'),
        );

        $this->assertEquals($expected, $this->loader->getFixtureFiles());
    }

    public function testLoad()
    {
        $this->container->setParameter('kernel.root_dir', realpath('./Tests/Fixtures/app'));
        $kernel = new TestKernel('test', false);
        $bundle = new AcmeDemoBundle();
        $kernel->setBundles(array($bundle));
        $kernel->boot();
        $this->container->set('kernel', $kernel);

        /**
         * Fixtures with values 'value2', 'value3' are defined with order 2,
         * those with 'value0', 'value1' with order 3.
         */

        $expected = array_map(
            function($item) {
                $object = new ExampleObject();
                $object->publicProperty = $item;

                return $object;
            },
            array('value2', 'value3', 'value0', 'value1')
        );

        ob_start();
        $this->loader->load($this->manager);
        ob_end_clean();

        $objects = $this->manager->all();

        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals(
                $expected[$i]->publicProperty,
                $objects[$i]->publicProperty
            );
            $this->assertEquals(
                $expected[$i]->getPrivatePropertyWithSetMethod(),
                $objects[$i]->getPrivatePropertyWithSetMethod()
            );
            $this->assertEquals(
                $expected[$i]->getPrivatePropertyWithAddMethod(),
                $objects[$i]->getPrivatePropertyWithAddMethod()
            );
        }
    }
}
