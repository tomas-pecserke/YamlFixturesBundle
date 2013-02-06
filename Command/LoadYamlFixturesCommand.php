<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomáš Pecsérke <tomas@pecserke.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Command;

use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Pecserke\YamlFixturesBundle\DataFixtures\ArrayFixturesLoader;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixturesLocator;
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
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load YaML fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the fixtures instead of deleting all data from the database first.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>pecserke:fixtures:yml:load</info> command loads YaML fixtures from your bundles:

  <info>./app/console pecserke:fixtures:yml:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:fixtures:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console pecserke:fixtures:yml:load --append</info>

By default Pecserke YaML Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console pecserke:fixtures:yml:load --purge-with-truncate</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orm = $this->getContainer()->has('doctrine') ? $this->getContainer()->get('doctrine') : null;
        $odm = $this->getContainer()->has('doctrine_mongodb') ? $this->getContainer()->get('doctrine_mongodb') : null;
        if ($orm === null && $odm === null) {
            throw new \InvalidArgumentException('doctrine ORM nor ODM is defined');
        }

        if ($input->isInteractive() && !$input->getOption('append')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', false)) {
                return;
            }
        }

        $fixtureLocator = new YamlFixturesLocator($this->getApplication()->getKernel());

        $dirOrFile = $input->getOption('fixtures');
        $fixtureFiles = array();
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $fixtureFiles = array_merge($fixtureFiles, $fixtureLocator->findInDirectory($path));
                } else if (strripos($path, '.yml') === 0 && !is_dir($path)) {
                    $fixtureFiles[] = $path;
                }
            }

            if (empty($fixtureFiles)) {
                throw new \InvalidArgumentException(
                    sprintf('Could not find any YaML fixtures files to load in: %s', "\n\n- ".implode("\n- ", $paths))
                );
            }
        } else {
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                $fixtureFiles = array_merge($fixtureFiles, $fixtureLocator->findInBundle($bundle->getName()));
            }
        }

        $fixturesData = (new YamlFixtureFileParser())->parse($fixtureFiles);
        if (empty($fixturesData)) {
            $output->writeln('  <info>No fixtures to load</info>');

            return;
        }

        if (!$input->getOption('append')) {
            $ormPurger = new ORMPurger();
            $truncate = $input->getOption('purge-with-truncate');
            $ormPurger->setPurgeMode($truncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
            foreach (($orm !== null ? $orm->getManagers() : array()) as $em) {
                $ormPurger->setEntityManager($em);
                $ormPurger->purge();
            }

            $odmPurger = new MongoDBPurger();
            foreach (($odm !== null ? $odm->getManagers() : array()) as $dm) {
                $odmPurger->setDocumentManager($dm);
                $odmPurger->purge();
            }
        }

        $loader = new ArrayFixturesLoader();
        $loader->setContainer($this->getContainer());
        $loader->setReferenceRepository(new ReferenceRepository());

        foreach ($fixturesData as $order => $fixtures) {
            foreach ($fixtures as $fixture) {
                $om = null;
                $class = $fixture['class'];
                if ($orm !== null) {
                    $om = $orm->getManagerForClass($class);
                } else if ($odm !== null) {
                    $om = $odm->getManagerForClass($class);
                }

                $file = $fixture['file'];

                if ($om === null) {
                    throw new \InvalidArgumentException("fixture '$class' is neither entity nor document - in '$file'");
                }

                $output->writeln("  <comment>></comment> <info>[$order] $class - $file</info>");
                $loader->load($fixture, $om);
            }
        }
    }
}
