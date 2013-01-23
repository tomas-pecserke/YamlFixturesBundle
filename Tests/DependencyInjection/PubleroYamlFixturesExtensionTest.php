<?php
namespace Publero\YamlFixturesBundle\Tests\DependencyInjection;

use Publero\YamlFixturesBundle\DataTransformer\ObjectTransformer;
use Publero\YamlFixturesBundle\DependencyInjection\PubleroYamlFixturesExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PubleroYamlFixturesExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new PubleroYamlFixturesExtension();
        $extension->load(array(), $container);
        $container->compile();

        $this->assertTrue($container->get('publero_fixtures.object_transformer') instanceof ObjectTransformer);
    }
}
