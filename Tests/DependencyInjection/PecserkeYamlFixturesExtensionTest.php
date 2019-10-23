<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\DependencyInjection;

use Exception;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\DependencyInjection\PecserkeYamlFixturesExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PecserkeYamlFixturesExtensionTest extends TestCase {
    /**
     * @throws Exception
     */
    public function testLoad() {
        $container = new ContainerBuilder();
        $extension = new PecserkeYamlFixturesExtension();
        $extension->load(array(), $container);
        $container->compile();

        $this->assertTrue($container->get('pecserke_fixtures.object_transformer') instanceof ObjectTransformer);
    }
}
