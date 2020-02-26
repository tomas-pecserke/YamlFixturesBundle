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

use Pecserke\YamlFixturesBundle\Autoloader\AutoloaderInterface;
use Pecserke\YamlFixturesBundle\Autoloader\DynamicFixtureArrayDataFixtureClassAutoloader;
use Pecserke\YamlFixturesBundle\DependencyInjection\Compiler\RegisterFixturesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PecserkeYamlFixturesBundle extends Bundle {
    /**
     * @var AutoloaderInterface
     */
    private $autoloader;

    public function __construct() {
        $this->autoloader = new DynamicFixtureArrayDataFixtureClassAutoloader();
    }

    public function build(ContainerBuilder $container): void {
        parent::build($container);
        $container->addCompilerPass(new RegisterFixturesCompilerPass($this->autoloader));
    }

    public function boot(): void {
        parent::boot();
        if (!$this->autoloader->isRegistered()) {
            $this->autoloader->register();
        }
    }

    public function shutdown(): void {
        if ($this->autoloader->isRegistered()) {
            $this->autoloader->unregister();
        }
        parent::shutdown();
    }
}
