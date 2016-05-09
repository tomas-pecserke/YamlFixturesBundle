<?php
namespace Pecserke\YamlFixturesBundle\Console\Helper;

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfirmationHelper extends Helper
{
    private $inputStream;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function ask(InputInterface $input, OutputInterface $output, $question, $default = false)
    {
        if (class_exists('Symfony\Component\Console\Helper\QuestionHelper')) {
            $helper = new \Symfony\Component\Console\Helper\QuestionHelper();
            if ($this->inputStream !== null) {
                $helper->setInputStream($this->inputStream);
            }
            $confirmation = new \Symfony\Component\Console\Question\ConfirmationQuestion($question, $default);

            return (bool) $helper->ask($input, $output, $confirmation);
        }

        $helper = new \Symfony\Component\Console\Helper\DialogHelper();
        if ($this->inputStream !== null) {
            $helper->setInputStream($this->inputStream);
        }

        return (bool) $helper->askConfirmation(
            $output,
            "<question>$question</question>",
            $default
        );
    }

    /**
     * Sets the input stream to read from when interacting with the user.
     *
     * This is mainly useful for testing purpose.
     *
     * @param resource $stream The input stream
     */
    public function setInputStream($stream)
    {
        $this->inputStream = $stream;
    }

    /**
     * Returns the helper's input stream.
     *
     * @return resource|null The input stream or null if the default STDIN is used
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    public function getName()
    {
        return 'confirmation';
    }
}
