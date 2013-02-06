<?php
namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Symfony\Component\HttpKernel\Kernel;

class YamlFixturesLocator
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Returns an array of file paths to YaML files in specified directory.
     *
     * @param string $directory
     * @throws \InvalidArgumentException
     * @return array
     */
    public function findInDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("'$directory' isn't a directory or can'ŧ read it");
        }

        $files = array_filter(
            scandir($directory),
            function($filename) use ($directory) {
                return strripos($filename, '.yml') === 0 && !is_dir("$directory/$filename");
            }
        );

        return array_map(
            function($filename) use ($directory) {
                return "$directory/$filename";
            },
            $files
        );
    }

    /**
     * Returns an array of file paths to YaML files in specified bundle.
     *
     * Bundle's fixtures are located in /path/to/bundle/Resources/fixtures or in
     * /application/root/path/Resources/bundleName/fixtures.
     * Those under application root path take precedens over those under bundle path.
     *
     * @param string $bundleName
     * @throws \InvalidArgumentException
     * @return array
     */
    public function findInBundle($bundleName)
    {
        $bundleDir = $this->kernel->getBundle($bundleName)->getPath() . '/Resources/fixtures';
        $appDir = $this->kernel->getRootDir() . '/Resources/' . $bundleName . '/fixtures';
        $appDirExists = is_dir($appDir);

        $bundleFixtureFiles = array();
        if (is_dir($bundleDir)) {
            foreach (scandir($bundleDir) as $file) {
                $filename = "$bundleDir/$file";
                if (strripos($file, '.yml') !== false && !is_dir($filename)) {
                    $overrideFilename = "$appDir/$file";
                    if ($appDirExists && file_exists($overrideFilename) && !is_dir($overrideFilename)) {
                        $bundleFixtureFiles[] = $overrideFilename;
                    } else {
                        $bundleFixtureFiles[] = $filename;
                    }
                }
            }
        }

        if ($appDirExists) {
            foreach (scandir($appDir) as $file) {
                $filename = "$appDir/$file";

                if (strripos($file, '.yml') !== false && !is_dir($filename)) {
                    if (!in_array($filename, $bundleFixtureFiles)) {
                        $bundleFixtureFiles[] = $filename;
                    }
                }
            }
        }

        return $bundleFixtureFiles;
    }
}
