<?php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
class appTestProjectContainer extends Container
{
    public function __construct()
    {
        $this->parameters = $this->getDefaultParameters();
        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();
        $this->set('service_container', $this);
        $this->scopes = array();
        $this->scopeChildren = array();
    }
    public function getParameter($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->parameters)) {
            throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }
        return $this->parameters[$name];
    }
    public function hasParameter($name)
    {
        return array_key_exists(strtolower($name), $this->parameters);
    }
    public function setParameter($name, $value)
    {
        throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }
    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $this->parameterBag = new FrozenParameterBag($this->parameters);
        }
        return $this->parameterBag;
    }
    protected function getDefaultParameters()
    {
        return array(
            'kernel.root_dir' => '/home/nh/workspace/PubleroYamlFixturesBundle/Tests/Fixtures/app',
            'kernel.environment' => 'test',
            'kernel.debug' => false,
            'kernel.name' => 'app',
            'kernel.cache_dir' => '/home/nh/workspace/PubleroYamlFixturesBundle/Tests/Fixtures/app/cache/test',
            'kernel.logs_dir' => '/home/nh/workspace/PubleroYamlFixturesBundle/Tests/Fixtures/app/logs',
            'kernel.bundles' => array(
                'Mock_Bundle_bf5ef23d' => 'Mock_Bundle_bf5ef23d',
            ),
            'kernel.charset' => 'UTF-8',
            'kernel.container_class' => 'appTestProjectContainer',
        );
    }
}
