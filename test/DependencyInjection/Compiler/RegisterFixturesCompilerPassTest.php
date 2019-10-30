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
use Pecserke\YamlFixturesBundle\Autoloader\DynamicFixtureArrayDataFixtureClassAutoloader;
use Pecserke\YamlFixturesBundle\Fixture\FixtureArrayDataFixture;
use Pecserke\YamlFixturesBundle\Loader\FixtureArrayDataLoaderInterface;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\Bundle\AcmeDemoBundle\AcmeDemoBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RegisterFixturesCompilerPassTest extends TestCase {
    private $autoloader;

    protected function setUp(): void {
        $this->autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
        $this->autoloader->register();
    }

    /**
     * @throws Exception
     */
    public function testRegistersFixturesAsTaggedServicesIncludingOverriding(): void {
        $projectDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Tests')
            . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Bundle';
        $bundles = [new AcmeDemoBundle()];

        $parameterBag = new ParameterBag();
        $container = new ContainerBuilder($parameterBag);
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->set(
            FixtureArrayDataLoaderInterface::class,
            $this->getMockForAbstractClass(FixtureArrayDataLoaderInterface::class)
        );

        $compilerPass = new RegisterFixturesCompilerPass();
        $compilerPass->process($container);
        $services = $container->findTaggedServiceIds(FixturesCompilerPass::FIXTURE_TAG);

        $this->assertCount(4, $services);
        foreach ($services as $id => $tagArguments) {
            $this->assertInstanceOf(FixtureArrayDataFixture::class, $container->get($id));
        }
    }

    protected function tearDown(): void {
        $this->autoloader->unregister();
    }
}
