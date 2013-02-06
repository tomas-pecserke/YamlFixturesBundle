<?php
namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Pecserke\YamlFixturesBundle\DataFixtures\ReferenceRepository;
use Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class ArrayFixturesLoader implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ReferenceRepository
     */
    private $referenceRepository;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setReferenceRepository(ReferenceRepository $referenceRepository)
    {
        $this->referenceRepository = $referenceRepository;
    }

    public function load(array $fixture, ObjectManager $manager)
    {
        if (!empty($fixture['transformer'])) {
            $transformer = $fixture['transformer']{0} == '@' ?
                $this->container->get(substr($fixture['transformer'], 1)) :
                new $fixture['transformer']()
            ;
        }
        $transformer = isset($transformer) ?
            $transformer :
            $this->container->get('pecserke_fixtures.object_transformer')
        ;
        if (!($transformer instanceof ObjectTransformerInterface)) {
            $class = get_class($transformer);
            $expected = 'Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface';
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

            $object = $transformer->transform($data, $fixture['class']);

            if (!empty($fixture['equal_condition'])) {
                $result = $this->getSame($object, $fixture['equal_condition'], $manager);
                if (count($result) > 0) {
                    $this->referenceRepository->addReference($referenceName, $result[0]);
                    continue;
                }
            }

            $manager->persist($object);
            $this->referenceRepository->addReference($referenceName, $object);

            if ($postPersist) {
                $params = [$object];
                if (!empty($postPersist[2])) {
                    $params = array_merge($params, $postPersist[2]);
                }
                $callback = [$postPersist[0], $postPersist[1]];
                call_user_func_array($callback, $params);
            }
        }

        $manager->flush();
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
                $expected = 'Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface';
                throw new \InvalidArgumentException("data transformer '$class' is not an instance of $expected");
            }
        }

        foreach ($array as $key => &$value) {
            if (is_string($value) && preg_match('/^([@#])[^\1]/', $value)) {
                $substring = substr($value, 1);
                switch ($value{0}) {
                    case '@':
                        $value = $this->referenceRepository->getReference($substring);
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
}
