<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DetectMangerRegistriesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $purgerDef = $container->getDefinition('pecserke_fixtures.purger');

        foreach ($container->getParameter('pecserke_fixtures.supported_registry_service_ids') as $serviceId) {
            if ($container->has($serviceId)) {
                $purgerDef->addMethodCall('addRegistry', [new Reference($serviceId)]);
            }
        }
    }
}
