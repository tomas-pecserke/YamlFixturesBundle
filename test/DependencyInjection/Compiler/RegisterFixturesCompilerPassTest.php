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
     * @var string
     */
    private $bundleName;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var string
     */
    private $bundleDir;

    /**
     * @var string
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
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('compilerTestDir'));

        $rootDir = vfsStream::url('compilerTestDir');
        $this->bundleDir = $rootDir . '/bundle';
        mkdir($this->bundleDir);
        $this->projectDir = $rootDir . '/project';
        mkdir($this->projectDir);

        $this->bundleName = 'TestBundle';

        $this->bundle = $this->getMockForAbstractClass(BundleInterface::class);
        $this->bundle->method('getPath')->willReturn($this->bundleDir);
        $this->bundle->method('getName')->willReturn($this->bundleName);

        $this->container = new ContainerBuilder(new ParameterBag());
        $this->container->setParameter('kernel.bundles', [$this->bundle]);
        $this->container->setParameter('kernel.project_dir', $this->projectDir);
        $this->container->set(
            FixtureArrayDataLoaderInterface::class,
            $this->getMockForAbstractClass(FixtureArrayDataLoaderInterface::class)
        );

        $this->compilerPass = new RegisterFixturesCompilerPass();

        $this->autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $this->autoloader->register();
    }

    /**
     * @throws Exception
     */
    public function testRegistersFixturesAsTaggedServicesIncludingOverriding(): void {
        mkdir($this->projectDir . '/fixtures', 0777, true);
        file_put_contents($this->projectDir . '/fixtures/example_1.yml', <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  order: 1
  data:
    example.object.4:
      publicProperty: value4
YaML
        );

        mkdir($this->bundleDir . '/Resources/fixtures', 0777, true);
        file_put_contents($this->bundleDir . '/Resources/fixtures/example_2.yml', <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  order: 3
  equal_condition: [ publicProperty ]
  data:
    example.object.0:
      publicProperty: value0
    example.object.1:
      publicProperty: value1
YaML
        );
        file_put_contents($this->bundleDir . '/Resources/fixtures/example_3.yaml', <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  order: 2
  equal_condition: [ publicProperty ]
  data:
YaML
        );
        $overrideDir = $this->projectDir . '/fixtures/bundles/' . $this->bundleName;
        mkdir($overrideDir, 0777, true);
        file_put_contents($overrideDir . '/example_2.yml', <<< YaML
- class: Pecserke\YamlFixturesBundle\Stubs\ExampleObject
  equal_condition: [ publicProperty ]
  data:
    example.object.2:
      publicProperty: value2
YaML
        );

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
                ['class' => ExampleObject::class, 'file' => $this->projectDir . '/fixtures/example_1.yml'],
                ['class' => ExampleObject::class, 'file' => $overrideDir . '/example_2.yml'],
                ['class' => ExampleObject::class, 'file' => $this->bundleDir . '/Resources/fixtures/example_3.yaml']
            ],
            $types
        );
    }

    protected function tearDown(): void {
        $this->autoloader->unregister();
    }
}
