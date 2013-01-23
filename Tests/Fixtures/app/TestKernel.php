<?php
namespace Publero\YamlFixturesBundle\Tests\Fixtures\app;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    /**
     * @var array
     */
    private $bundlesToRegister = array();

    public function registerBundles()
    {
        return $this->bundlesToRegister;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    public function setBundles(array $bundles)
    {
        $this->bundlesToRegister = $bundles;
    }
}
