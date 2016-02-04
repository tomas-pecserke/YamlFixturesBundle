<?php
namespace Pecserke\YamlFixturesBundle\Tests\DependencyInjection;

use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Pecserke\YamlFixturesBundle\DependencyInjection\PecserkeYamlFixturesExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PecserkeYamlFixturesExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new PecserkeYamlFixturesExtension();
        $extension->load(array(), $container);
        $container->compile();

        $this->assertTrue($container->get('pecserke_fixtures.object_transformer') instanceof ObjectTransformer);
    }
}
