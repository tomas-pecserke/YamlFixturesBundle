<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle;

use Pecserke\YamlFixturesBundle\Autoloader\DynamicFixtureArrayDataFixtureClassAutoloader;
use Pecserke\YamlFixturesBundle\DependencyInjection\Compiler\RegisterFixturesCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PecserkeYamlFixturesBundleTest extends TestCase {
    private $bundle;

    protected function setUp(): void {
        $this->bundle = new PecserkeYamlFixturesBundle();
    }

    public function testBuild(): void {
        $container = new ContainerBuilder();
        $this->bundle->build($container);

        $passes = array_filter(
            $container->getCompilerPassConfig()->getPasses(),
            static function ($pass) {
                return $pass instanceof RegisterFixturesCompilerPass;
            }
        );

        $this->assertCount(1, $passes);
        $this->assertInstanceOf(RegisterFixturesCompilerPass::class, array_pop($passes));
    }

    public function testBootAndShutdownRegistersAndUnregistersAutoloader(): void {
        $filter = static function (callable $fn) {
            return $fn instanceof DynamicFixtureArrayDataFixtureClassAutoloader;
        };

        $this->bundle->boot();
        $autoloaders = array_filter(spl_autoload_functions(), $filter);
        $this->assertCount(1, $autoloaders);

        $this->bundle->shutdown();
        $autoloaders = array_filter(spl_autoload_functions(), $filter);
        $this->assertEmpty($autoloaders);
    }
}
