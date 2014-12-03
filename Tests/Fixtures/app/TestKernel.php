<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Fixtures\app;

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
