<?php
namespace Publero\YamlFixturesBundle\DataFixtures;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('publero:fixtures:load')
            ->setDescription('Load YaML fixtures to your database.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>publero:fixtures:load</info> command loads YaML fixtures from your bundles:

  <info>./app/console publero:fixtures:load</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console publero:fixtures:load --append</info>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console publero:fixtures:load --purge-with-truncate</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input->setArgument('command', 'doctrine:fixtures:load');
        $input->setArgument('fixtures', realpath($bundle->getPath() . '/DataFixtures/'));

        $command = $this->getApplication()->find('doctrine:fixtures:load');

        return $command->run($input, $output);
    }
}
