<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DependencyInjection;

use Exception;
use Pecserke\YamlFixturesBundle\Transformer\ObjectTransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PecserkeYamlFixturesExtensionTest extends TestCase {
    /**
     * @throws Exception
     */
    public function testLoadingRegistersDefaultObjectTransformer(): void {
        $container = new ContainerBuilder();
        $extension = new PecserkeYamlFixturesExtension();
        $extension->load(
            [
                'pecserke_yaml_fixtures' => [
                    'fixture_class_prefix' => 'Test\\ClassName\\Prefix\\'
                ]
            ],
            $container
        );
        $container->compile();


        $this->assertEquals(
            'Test\\ClassName\\Prefix\\',
            $container->getParameter('pecserke_yaml_fixtures.fixture_class_prefix')
        );
        $this->assertInstanceOf(
            ObjectTransformerInterface::class,
            $container->get('pecserke_yaml_fixtures.object_transformer.default')
        );
    }
}
