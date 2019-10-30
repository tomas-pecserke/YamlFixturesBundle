<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DependencyInjection\Compiler;

use Pecserke\YamlFixturesBundle\DependencyInjection\Compiler\RegisterFixturesCompilerPass;
use Pecserke\YamlFixturesBundle\Tests\Fixtures\Bundle\AcmeDemoBundle\AcmeDemoBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RegisterFixturesCompilerPassTest extends TestCase {
    public function test_process_bundleWithOverride_processesCorrectly(): void {
        $projectDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Bundle';
        $bundles = [new AcmeDemoBundle()];

        $parameterBag = new ParameterBag();
        $container = new ContainerBuilder($parameterBag);
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.project_dir', $projectDir);

        $compilerPass = new RegisterFixturesCompilerPass();
        $compilerPass->process($container);

        $this->assertCount(5, $container->getDefinitions()); // 4 fixtures + the container itself
    }
}
