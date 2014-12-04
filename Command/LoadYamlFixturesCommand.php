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

use Pecserke\YamlFixturesBundle\DataFixtures\ArrayFixturesLoader;
use Pecserke\YamlFixturesBundle\DataFixtures\Locator\BundleResourcesLocator;
use Pecserke\YamlFixturesBundle\DataFixtures\Locator\FilesystemLocator;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataFixtures\YamlFixtureFileParser;
use Pecserke\YamlFixturesBundle\Purger\Purger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class LoadYamlFixturesCommand extends ContainerAwareCommand
{
    const HELP = <<<EOT
The <info>pecserke:fixtures:yml:load</info> command loads YaML fixtures from your bundles:

  <info>./app/console pecserke:fixtures:yml:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console pecserke:fixtures:yml:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console pecserke:fixtures:yml:load --append</info>

By default Pecserke YaML Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console pecserke:fixtures:yml:load --purge-with-truncate</info>
EOT;
    const PURGE_CONFIRMATION = '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>';

    private $supportedDoctrines = array('doctrine', 'doctrine_mongodb', 'doctrine_phpcr');

    protected function configure()
    {
        $this
            ->setName('pecserke:fixtures:yml:load')
            ->setDescription('Load YaML fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load YaML fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the fixtures instead of deleting all data from the database first.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(self::HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->verifyDoctrine()) {
            throw new \InvalidArgumentException('no doctrine ORM nor ODM is defined');
        }

        $fixturesData = $this->loadFixtureData($input->getOption('fixtures'));
        if (empty($fixturesData)) {
            $output->writeln('  <info>No fixtures to load</info>');

            return;
        }

        if (!$input->getOption('append')) {
            if ($input->isInteractive()) {
                /* @var DialogHelper $dialog */
                $dialog = $this->getHelperSet()->get('dialog');
                if (!$dialog->askConfirmation($output, self::PURGE_CONFIRMATION, false)) {
                    return;
                }
            }
            /* @var Purger $purger */
            $purger = $this->getContainer()->get('pecserke_fixtures.purger');
            $purger->purge($input->getOption('purge-with-truncate'));
        }

        $this->loadFixtures($output, $fixturesData);
    }

    /**
     * @return bool
     */
    private function verifyDoctrine()
    {
        foreach ($this->supportedDoctrines as $service) {
            if ($this->getContainer()->has($service)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array|string|null $fixtures
     * @return array
     */
    private function loadFixtureData($fixtures)
    {
        $fixtureFiles = $this->locateFixtureFiles($fixtures);
        $parser = new YamlFixtureFileParser();

        return $parser->parse($fixtureFiles);
    }

    /**
     * @param array|string|null $fixturePaths
     * @return array
     */
    private function locateFixtureFiles($fixturePaths)
    {
        if (empty($fixturePaths)) {
            return $this->locateFixtureFilesInBundles();
        }

        $fixturePaths = is_array($fixturePaths) ? $fixturePaths : array($fixturePaths);
        $fixtureFiles = $this->locateFixtureFilesInDirs($fixturePaths);
        if (empty($fixtureFiles)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find any YaML fixtures files to load in: %s',
                "\n\n- " . implode("\n- ", $fixturePaths)
            ));
        }

        return $fixtureFiles;
    }

    /**
     * @param array $fixturePaths
     * @return array
     */
    private function locateFixtureFilesInDirs(array $fixturePaths)
    {
        $fixtureFiles = array();
        $fixtureLocator = new FilesystemLocator();
        foreach ($fixturePaths as $path) {
            if (is_dir($path)) {
                $fixtureFiles = array_merge($fixtureFiles, $fixtureLocator->find($path));
            } else {
                $fixtureFiles[] = $path;
            }
        }

        return $fixtureFiles;
    }

    /**
     * @return array
     */
    private function locateFixtureFilesInBundles()
    {
        /* @var Application $app */
        $app = $this->getApplication();
        /* @var Kernel $kernel */
        $kernel = $app->getKernel();
        $fixtureLocator = new BundleResourcesLocator($kernel);

        $fixtureFiles = array();
        foreach ($kernel->getBundles() as $bundle) {
            $fixtureFiles[] = $fixtureLocator->find($bundle->getName());
        }

        return call_user_func_array('array_merge', $fixtureFiles);
    }

    private function loadFixtures(OutputInterface $output, array $fixturesData)
    {
        $orm = $this->getContainer()->has('doctrine') ? $this->getContainer()->get('doctrine') : null;
        $odm = $this->getContainer()->has('doctrine_mongodb') ? $this->getContainer()->get('doctrine_mongodb') : null;

        $loader = new ArrayFixturesLoader();
        $loader->setContainer($this->getContainer());
        $loader->setReferenceRepository(new ReferenceRepository());

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
                    throw new \InvalidArgumentException("fixture '$class' is neither entity nor document - in '$file'");
                }

                $output->writeln("  <comment>></comment> <info>[$order] $class - $file</info>");
                $loader->load($fixture, $om);
            }
        }
    }
}
