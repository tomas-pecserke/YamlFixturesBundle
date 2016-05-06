<?php
namespace Pecserke\YamlFixturesBundle\Tests\Console\Helper;

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Pecserke\YamlFixturesBundle\Console\Helper\ConfirmationHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class ConfirmationHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testAskYes()
    {
        $helper = new ConfirmationHelper();
        $helper->setInputStream($this->getInputStream("Y\n"));

        $this->assertTrue($helper->ask($this->createInputInterfaceMock(), $this->createOutputInterface(), ""));
    }

    public function testAskNo()
    {
        $helper = new ConfirmationHelper();
        $helper->setInputStream($this->getInputStream("n\n"));

        $this->assertFalse($helper->ask($this->createInputInterfaceMock(), $this->createOutputInterface(), ""));
    }

    public function testGetSetInputStream()
    {
        $helper = new ConfirmationHelper();
        $inputStream = $this->getInputStream("n\n");
        $helper->setInputStream($inputStream);

        $this->assertSame($inputStream, $helper->getInputStream());
    }

    public function testGetName()
    {
        $helper = new ConfirmationHelper();

        $this->assertEquals("confirmation", $helper->getName());
    }

    /**
     * @param string $input
     * @return resource
     */
    private function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    /**
     * @return StreamOutput
     */
    protected function createOutputInterface()
    {
        return new StreamOutput(fopen('php://memory', 'r+', false));
    }

    /**
     * @param bool $interactive
     * @return InputInterface
     */
    protected function createInputInterfaceMock($interactive = true)
    {
        $mock = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $mock->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue($interactive));

        return $mock;
    }
}
