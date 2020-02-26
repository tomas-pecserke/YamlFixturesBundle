<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\Autoloader\DynamicFixtureArrayDataFixtureClassAutoloader;
use Pecserke\YamlFixturesBundle\Fixture\FixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;
use Pecserke\YamlFixturesBundle\Stubs\ExampleObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class RegisterFixturesCompilerPassTest extends TestCase {
    /**
     * @var DynamicFixtureArrayDataFixtureClassAutoloader
     */
    private $autoloader;

    /**
     * @var BundleInterface|MockObject
     */
    private $bundle;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var vfsStreamDirectory
     */
    private $bundleDir;

    /**
     * @var vfsStreamDirectory
     */
    private $projectDir;

    /**
     * @var RegisterFixturesCompilerPass
     */
    private $compilerPass;

    /**
     * @throws vfsStreamException
     */
    public function setUp(): void {
        $rootDir = vfsStream::setup('locatorTestDir', null, ['bundle' => [], 'project' => []]);
        $this->bundleDir = $rootDir->getChild('bundle');
        $this->projectDir = $rootDir->getChild('project');
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot($rootDir);

        $this->bundle = $this->getMockForAbstractClass(BundleInterface::class);
        $this->bundle->method('getName')->willReturn('TestBundle');
        $this->bundle->method('getPath')->willReturn($this->bundleDir->url());

        $this->container = new ContainerBuilder(new ParameterBag());
        $this->container->setParameter('kernel.bundles', [$this->bundle]);
        $this->container->setParameter('kernel.project_dir', $this->projectDir->url());
        $this->container->setParameter('kernel.project_dir', $this->projectDir->url());
        $this->container->setParameter('pecserke_yaml_fixtures.fixture_class_prefix', 'App\\Migrations\\');
        $this->container->set(
            FixtureArrayDataLoaderInterface::class,
            $this->getMockForAbstractClass(FixtureArrayDataLoaderInterface::class)
        );

        $this->autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $this->autoloader->register();

        $this->compilerPass = new RegisterFixturesCompilerPass($this->autoloader);
    }

    /**
     * @throws Exception
     */
    public function test_process_registersFixturesAsTaggedServicesIncludingOverriding(): void {
        $bundleStructure = ['Resources' => ['fixtures' => [
            'example_2.yml' => <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  order: 3
  equal_condition: [ publicProperty ]
  data:
    example.object.0:
      publicProperty: value0
    example.object.1:
      publicProperty: value1
YaML
            ,
            'example_3.yaml' => <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  order: 2
  equal_condition: [ publicProperty ]
  data:
YaML
        ]]];
        vfsStream::create($bundleStructure, $this->bundleDir);

        $projectStructure = ['fixtures' => [
            'bundles' => [$this->bundle->getName() => [
                'example_2.yml' => <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  dependencies: [ bundle_example_1 ]
  equal_condition: [ publicProperty ]
  data:
    example.object.2:
      publicProperty: value2
YaML
            ]],
            'example_1.yml' => <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  set_name: bundle_example_1
  order: 1
  data:
    example.object.4:
      publicProperty: value4
YaML
        ]];
        vfsStream::create($projectStructure, $this->projectDir);

        $overrideDir = $this->projectDir->url() . '/fixtures/bundles/' . $this->bundle->getName();
        $this->compilerPass->process($this->container);
        $taggedServiceIds = $this->container->findTaggedServiceIds(FixturesCompilerPass::FIXTURE_TAG);
        $services = array_map([$this->container, 'get'], array_keys($taggedServiceIds));

        $this->assertCount(3, $services);
        foreach ($services as $service) {
            $this->assertInstanceOf(FixtureArrayDataFixture::class, $service);
        }

        $types = array_map(
            static function (FixtureArrayDataFixture $fixture) {
                return ['class' => $fixture->getFixtureData()['class'], 'file' => $fixture->getFixtureData()['file']];
            },
            $services
        );
        $this->assertEquals(
            [
                ['class' => ExampleObject::class, 'file' => $this->projectDir->url() . '/fixtures/example_1.yml'],
                ['class' => ExampleObject::class, 'file' => $overrideDir . '/example_2.yml'],
                ['class' => ExampleObject::class, 'file' => $this->bundleDir->url() . '/Resources/fixtures/example_3.yaml']
            ],
            $types
        );
    }

    protected function tearDown(): void {
        $this->autoloader->unregister();
    }
}
