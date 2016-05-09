<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Command;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadYamlFixturesCommandTest extends \PHPUnit_Framework_TestCase
{
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

    protected function setUp()
    {
        $this->container = new ContainerBuilder();

        $this->kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\KernelInterface');
        $this->kernel->method('getContainer')->willReturn($this->container);

        $this->application = new Application($this->kernel);
        $this->application->add(new LoadYamlFixturesCommand());

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testDir'));
    }

    public function testExecute()
    {
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
        $doctrine = $this->getMockForAbstractClass('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->container->set('doctrine', $doctrine);
        $this->container->set('pecserke_fixtures.object_transformer', new ObjectTransformer());
        $referenceRepository = new ReferenceRepository();
        $this->container->set('pecserke_fixtures.reference_repository', $referenceRepository);

        $om = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ObjectManager');
        $doctrine->method('getManagerForClass')->willReturn($om);

        $repository = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ObjectRepository');
        $om->method('getRepository')->willReturn($repository);

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

        $this->assertInstanceOf('Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject', $entity1);
        $this->assertInstanceOf('Pecserke\YamlFixturesBundle\Tests\Fixtures\DataTransformer\ExampleObject', $entity2);
        $this->assertEquals('value1', $entity1->publicProperty);
        $this->assertEquals('value2', $entity2->publicProperty);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^Could not find any YaML fixtures files to load in: /
     */
    public function testExecuteNoFixtures()
    {
        $fixtureRoot = vfsStream::url('testDir');

        $this->kernel->method('getBundles')->willReturn(array());
        $doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->getMockForAbstractClass()
        ;
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
