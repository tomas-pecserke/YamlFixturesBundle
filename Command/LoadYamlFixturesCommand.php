<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle
*
* The code was originally distributed inside the Symfony framework.
*
* (c) Tomáš Pecsérke <tomas@pecserke.eu>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadYamlFixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pecserke:fixtures:yml:load')
            ->setDescription('Load YaML fixtures to your database.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>pecserke:fixtures:yml:load</info> command loads YaML fixtures from your bundles:

  <info>./app/console pecserke:fixtures:yml:load</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console pecserke:fixtures:yml:load --append</info>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console pecserke:fixtures:yml:load --purge-with-truncate</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $this->getContainer()->get('kernel')->getBundle('PecserkeYamlFixturesBundle');
        $input->setArgument('command', 'doctrine:fixtures:load');
        $input->setArgument('fixtures', realpath($bundle->getPath() . '/DataFixtures/'));

        $command = $this->getApplication()->get('doctrine:fixtures:load');

        return $command->run($input, $output);
    }
}
