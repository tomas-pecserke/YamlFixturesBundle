<?php
namespace Publero\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Publero\YamlFixturesBundle\DataTransformer\DataTransformerInterface;
use Publero\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class YamlFixturesLoader extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function getOrder()
    {
        return 1;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $rootDir = realpath(realpath($this->container->getParameter('kernel.root_dir')) . '/..');
        $filesystem = new Filesystem();

        foreach ($this->getFixtures() as $order => $fixtures) {
            foreach ($fixtures as $fixture) {
                $file = rtrim($filesystem->makePathRelative($fixture['file'], $rootDir), '/');
                unset($fixture['file']);

                echo "Loading [$order] " . $fixture['class'] . " - $file\n";
                $this->loadFixture($fixture, $manager);
            }
            $manager->flush();
        }
    }

    public function loadFixture(array $fixture, ObjectManager $manager)
    {
        if (!empty($fixture['transformer'])) {
            $transformer = $fixture['transformer']{0} == '@' ?
                $this->container->get(substr($fixture['transformer'], 1)) :
                new $fixture['transformer']()
            ;
        }
        $transformer = isset($transformer) ?
            $transformer :
            $this->container->get('publero_fixtures.object_transformer')
        ;
        if (!($transformer instanceof ObjectTransformerInterface)) {
            $class = get_class($transformer);
            $expected = 'Publero\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface';
            throw new \InvalidArgumentException("data transformer '$class' is not an instance of $expected");
        }

        foreach ($fixture['data'] as $referenceName => $data) {
            $data = $this->parse($data);

            $postPersist = isset($data['@postPersist']) ? $data['@postPersist'] : null;
            unset($data['@postPersist']);
            if ($postPersist !== null) {
                if (empty($postPersist[0]) || !is_object($postPersist[0])) {
                    throw new \InvalidArgumentException('postPersist callback argument 1 must be an object');
                } else if (empty($postPersist[1]) || !method_exists($postPersist[0], $postPersist[1])) {
                    throw new \InvalidArgumentException(
                        'postPersist callback argument 2 must be a method on argument 1 object'
                    );
                }
            }

            $object = $transformer->transform($data, $fixture['class'], $this->referenceRepository);

            if (!empty($fixture['equal_condition'])) {
                $result = $this->getSame($object, $fixture['equal_condition'], $manager);
                if (count($result) > 0) {
                    // $this->addReference($referenceName, $result[0]);
                    $this->setReference($referenceName, $result[0]);
                    continue;
                }
            }

            $manager->persist($object);
            $this->addReference($referenceName, $object);

            if ($postPersist) {
                $params = [$object];
                if (!empty($postPersist[2])) {
                    $params = array_merge($params, $postPersist[2]);
                }
                $callback = [$postPersist[0], $postPersist[1]];
                call_user_func_array($callback, $params);
            }
        }
    }

    protected function parse(array $array)
    {
        $dataTransformer = !empty($array['@dataTransformer']) ? $array['@dataTransformer'] : null;
        unset($array['@dataTransformer']);
        if ($dataTransformer !== null) {
            $dataTransformer = $dataTransformer{0} == '@' ?
                $this->container->get(substr($dataTransformer, 1)) :
                new $dataTransformer()
            ;

            if (!($dataTransformer instanceof DataTransformerInterface)) {
                $class = get_class($dataTransformer);
                $expected = 'Publero\YamlFixturesBundle\DataTransformer\DataTransformerInterface';
                throw new \InvalidArgumentException("data transformer '$class' is not an instance of $expected");
            }
        }

        foreach ($array as $key => &$value) {
            if (is_string($value) && preg_match('/^([@#])[^\1]/', $value)) {
                $substring = substr($value, 1);
                switch ($value{0}) {
                    case '@':
                        $value = $this->getReference($substring);
                        break;
                    case '#':
                        $value = $this->container->getParameter($substring);
                        break;
                }
            } else if (is_array($value)) {
                $value = $this->parse($value);
            }
        }

        if (!empty($dataTransformer)) {
            return $dataTransformer->transform($array);
        }

        return $array;
    }

    protected function getSame($object, array $equalCondition, ObjectManager $manager)
    {
        $conditions = [];
        $publicVariables = get_object_vars($object);

        foreach ($equalCondition as $field) {
            $getMethodName = 'get' . ucfirst($field);
            $isMethodName = 'is' . ucfirst($field);
            if (array_key_exists($field, $publicVariables)) {
                $conditions[$field] = $object->$field;
            } else if (method_exists($object, $getMethodName)) {
                $conditions[$field] = $object->$getMethodName();
            } else if (method_exists($object, $getMethodName)) {
                $conditions[$field] = $object->$getMethodName();
            } else {
                $class = get_class($object);
                throw new \InvalidArgumentException("object '$class' doesn't have property '$field'");
            }
        }

        return $manager->getRepository(get_class($object))->findBy($conditions);
    }

    /**
     * Returns array of files to load.
     *
     * Fixture files are looked for at <bundle_root>/Resources/fixtures, and app/Resources/<bundle_name>/fixtures
     * for all bundles registered with kernel. Files in app/Resources override those in bundle resources.
     *
     * @return string[]
     */
    public function getFixtureFiles()
    {
        $fixtures = [];
        $kernelDir = $this->container->getParameter('kernel.root_dir');

        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            $dir = $bundle->getPath() . '/Resources/fixtures';
            $appDir = $kernelDir . '/Resources/' . $bundle->getName() . '/fixtures';
            $ymlFilesFilter = function ($path) {
                return preg_match('/\.yml$/i', $path);
            };

            $files = [];
            if (is_dir($dir) && ($files = scandir($dir)) !== false) {
                $files = array_filter($files, $ymlFilesFilter);
            }

            $appFiles = [];
            if (is_dir($appDir) && ($appFiles = scandir($appDir)) !== false) {
                $appFiles = array_filter($appFiles, $ymlFilesFilter);
            }

            foreach (array_diff($files, $appFiles) as $filename) {
                $fixtures[] = realpath("$dir/$filename");
            }
            foreach ($appFiles as $filename) {
                $fixtures[] = realpath("$appDir/$filename");
            }
        }

        return $fixtures;
    }

    /**
     * Returns sorted array of arrays of fixtures.
     *
     * @return fixture[order][]
     */
    protected function getFixtures()
    {
        $files = $this->getFixtureFiles();
        $fixturesData = array_map(
            function ($filename)
            {
                return Yaml::parse($filename) ? : [];
            }, $files);

        $fixturesData = array_combine($files, $fixturesData);

        $sorted = [];
        $unsorted = [];
        foreach ($fixturesData as $file => $fixtures) {
            foreach ($fixtures as $class => $fixture) {
                $fixture['file'] = $file;
                $fixture['class'] = $class;
                $order = $fixture['order'] ? : null;
                unset($fixture['order']);
                if ($order !== null) {
                    $sorted[$order][] = $fixture;
                } else {
                    $unsorted[] = $fixture;
                }
            }
        }

        ksort($sorted, SORT_NUMERIC);
        if (!empty($unsorted)) {
            $sorted[] = $unsorted;
        }

        return $sorted;
    }
}
