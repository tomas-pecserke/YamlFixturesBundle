<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Command;

use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\DataFixtures\ArrayFixturesLoader;
use Pecserke\YamlFixturesBundle\DataFixtures\FixtureObjectArrayDataEvaluator;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixturesLocator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadYamlFixturesCommand extends Command implements ContainerAwareInterface {
    /**
     * @var ContainerInterface|null
     */
    private $container;

    protected function configure() {
        $this
            ->setName('pecserke:fixtures:yml:load')
            ->setDescription('Load YaML fixtures to your database.')
            ->addOption(
                'fixtures',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The directory or file to load YaML fixtures from.'
            )
            ->addOption(
                'append',
                null,
                InputOption::VALUE_NONE,
                'Append the fixtures instead of deleting all data from the database first.'
            )
            ->addOption(
                'purge-with-truncate',
                null, InputOption::VALUE_NONE,
                'Purge data by using a database-level TRUNCATE statement'
            )
            ->setHelp(<<<EOT
The <info>pecserke:fixtures:yml:load</info> command loads YaML fixtures from your bundles:

  <info>./app/console pecserke:fixtures:yml:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console pecserke:fixtures:yml:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console pecserke:fixtures:yml:load --append</info>

By default Pecserke YaML Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console pecserke:fixtures:yml:load --purge-with-truncate</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /* @var RegistryInterface $orm */
        $orm = $this->getContainer()->has('doctrine') ? $this->getContainer()->get('doctrine') : null;
        /* @var RegistryInterface $odm */
        $odm = $this->getContainer()->has('doctrine_mongodb')
            ? $this->getContainer()->get('doctrine_mongodb')
            : null;
        if ($orm === null && $odm === null) {
            throw new InvalidArgumentException('doctrine ORM nor ODM is defined');
        }

        if ($input->isInteractive() && !$input->getOption('append')) {
            $helper = $this->getHelper('question');
            if (!$helper->ask(
                $input,
                $output,
                new ConfirmationQuestion('Careful, database will be purged. Do you want to continue?')
            )) {
                return;
            }
        }

        /* @var Application $app */
        $app = $this->getApplication();
        /* @var KernelInterface $kernel */
        $kernel = $app->getKernel();

        $fixtureFiles = $this->locateFixtures($kernel, $input->getOption('fixtures'));

        $parser = new YamlFixtureFileParser();
        $fixturesData = $parser->parse($fixtureFiles);
        if (empty($fixturesData)) {
            $output->writeln('  <info>No fixtures to load</info>');

            return;
        }

        if (!$input->getOption('append')) {
            $this->purge($orm, $odm, $input->getOption('purge-with-truncate'));
        }

        $this->load($fixturesData, $output, $orm, $odm);
    }

    /**
     * @param KernelInterface $kernel
     * @param bool $dirOrFile
     * @return string[]
     */
    private function locateFixtures(KernelInterface $kernel, $dirOrFile) {
        $fixtureFiles = array();
        $fixtureLocator = new YamlFixturesLocator($kernel);
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $fixtureFiles = array_merge($fixtureFiles, $fixtureLocator->findInDirectory($path));
                } else {
                    $fixtureFiles[] = $path;
                }
            }

            if (empty($fixtureFiles)) {
                throw new InvalidArgumentException(sprintf(
                    'Could not find any YaML fixtures files to load in: %s',
                    "\n\n- " . implode("\n- ", $paths)
                ));
            }
        } else {
            foreach ($kernel->getBundles() as $bundle) {
                $fixtureFiles = array_merge($fixtureFiles, $fixtureLocator->findInBundle($bundle->getName()));
            }
        }

        return $fixtureFiles;
    }

    /**
     * @param RegistryInterface $orm
     * @param RegistryInterface $odm
     * @param bool $truncate
     */
    private function purge(RegistryInterface $orm, RegistryInterface $odm, $truncate) {
        $ormPurger = new ORMPurger();
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

    private function load(
        array $fixturesData,
        OutputInterface $output,
        RegistryInterface $orm = null,
        RegistryInterface $odm = null
    ) {
        $evaluator = new FixtureObjectArrayDataEvaluator();
        $evaluator->setContainer($this->getContainer());
        /* @var ReferenceRepository $referenceRepository */
        $referenceRepository = $this->getContainer()->get('pecserke_fixtures.reference_repository');
        $evaluator->setReferenceRepository($referenceRepository);

        $loader = new ArrayFixturesLoader($evaluator);

        foreach ($fixturesData as $order => $fixtures) {
            foreach ($fixtures as $fixture) {
                $om = null;
                $class = $fixture['class'];
                if ($orm !== null) {
                    $om = $orm->getManagerForClass($class);
                }
                if ($om === null && $odm !== null) {
                    $om = $odm->getManagerForClass($class);
                }

                $file = $fixture['file'];

                if ($om === null) {
                    throw new InvalidArgumentException(
                        "fixture '$class' is neither entity nor document - in '$file'"
                    );
                }

                $output->writeln("  <comment>></comment> <info>[$order] $class - $file</info>");
                $loader->load($fixture, $om);
            }
        }
    }

    public function getContainer(): ?ContainerInterface {
        return $this->container;
    }

    public function setContainer(?ContainerInterface $container = null): void {
        $this->container = $container;
    }
}
