<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Command;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\PropertyChangedListener;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadYamlFixturesCommandTest extends TestCase {
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @throws vfsStreamException
     */
    protected function setUp(): void {
        $this->container = new ContainerBuilder();

        $this->kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $this->kernel->method('getContainer')->willReturn($this->container);
        $this->kernel->method('getBundles')->willReturn([]);

        $this->application = new Application($this->kernel);
        $this->application->add(new LoadYamlFixturesCommand());

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testExecute(): void {
        $fixtureRoot = vfsStream::url('testDir');
        file_put_contents($fixtureRoot . '/test.yml', <<< EOF
Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject:
    equal_condition: [ publicProperty ]
    data:
        example.object.1:
            publicProperty: value1
        example.object.2:
            publicProperty: value2
EOF
        );

        $this->kernel->method('getBundles')->willReturn(array());
        $doctrine = $this->getMockForAbstractClass(RegistryInterface::class);
        $this->container->set('doctrine', $doctrine);
        $this->container->set('pecserke_fixtures.object_transformer', new ObjectTransformer());

        /* @var MockObject|ObjectManager $om */
        $om = $this->getMockBuilder(ObjectManager::class)
            ->addMethods(['getUnitOfWork'])
            ->getMockForAbstractClass();
        $doctrine->method('getManagerForClass')->willReturn($om);

        $uow = $this->getMockBuilder(PropertyChangedListener::class)
            ->addMethods(['isInIdentityMap'])
            ->getMockForAbstractClass();
        $om->method('getUnitOfWork')->willReturn($uow);

        $referenceRepository = new ReferenceRepository($om);
        $this->container->set('pecserke_fixtures.reference_repository', $referenceRepository);

        $repository = $this->getMockForAbstractClass(ObjectRepository::class);
        $om->method('getRepository')->willReturn($repository);
        $repository->method('findBy')->willReturn([]);

        $command = $this->application->find('pecserke:fixtures:yml:load');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            '--fixtures' => array($fixtureRoot),
            '--append' => true
        ));

        $entities = $referenceRepository->getReferences();

        $this->assertCount(2, $entities);
        $this->assertArrayHasKey('example.object.1', $entities);
        $this->assertArrayHasKey('example.object.2', $entities);

        $entity1 = $entities['example.object.1'];
        $entity2 = $entities['example.object.2'];

        $this->assertInstanceOf(ExampleObject::class, $entity1);
        $this->assertInstanceOf(ExampleObject::class, $entity2);
        $this->assertEquals('value1', $entity1->publicProperty);
        $this->assertEquals('value2', $entity2->publicProperty);
    }

    public function testExecuteNoFixtures(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Could not find any YaML fixtures files to load in: /');

        $fixtureRoot = vfsStream::url('testDir');

        $this->kernel->method('getBundles')->willReturn(array());
        $doctrine = $this->getMockBuilder(RegistryInterface::class)
            ->getMockForAbstractClass();
        $this->container->set('doctrine', $doctrine);

        $command = $this->application->find('pecserke:fixtures:yml:load');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            '--fixtures' => array($fixtureRoot),
            '--append' => true
        ));
    }
}
