<?php
namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayFixturesLoader implements ContainerAwareInterface
{
    /**
     * @var string
     */
    const POST_PERSIST_ANNOTATION = '@postPersist';

    /**
     * @var string
     */
    const DATA_TRANSFORMER_ANNOTATION = '@dataTransformer';

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
            $transformer = ($fixture['transformer']{0} === '@') ?
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
            $postPersist = isset($data[self::POST_PERSIST_ANNOTATION]) ? $data[self::POST_PERSIST_ANNOTATION] : null;
            unset($data[self::POST_PERSIST_ANNOTATION]);

            $data = $this->parse($data);
            $object = $transformer->transform($data, $fixture['class']);

            if (!empty($fixture['equal_condition'])) {
                $result = $this->getSame($object, $fixture['equal_condition'], $manager);
                if (count($result) > 0) {
                    $this->referenceRepository->addReference($referenceName, array_shift($result));
                    continue;
                }
            }

            $manager->persist($object);
            $this->referenceRepository->addReference($referenceName, $object);

            if ($postPersist !== null) {
                if (!is_array($postPersist)) {
                    throw new \InvalidArgumentException(sprintf(
                        'invalid postPersist callback at "%s": array [$object, $method, $params] expected, %s given',
                        $referenceName,
                        gettype($postPersist)
                    ));
                }
                $postPersist = $this->parse($postPersist);
                if (!is_object($postPersist[0])) {
                    throw new \InvalidArgumentException(sprintf(
                        'invalid postPersist callback at "%s": argument 1: object expected, %s given',
                        $referenceName,
                        gettype($postPersist[0])
                    ));
                }
                if (!method_exists($postPersist[0], $postPersist[1])) {
                    throw new \InvalidArgumentException(sprintf(
                        'invalid postPersist callback at "%s": method %s::%s does not exist',
                        $referenceName,
                        get_class($postPersist[0]),
                        $postPersist[1]
                    ));
                }

                call_user_func_array(array($postPersist[0], $postPersist[1]), $postPersist[2]);
            }
        }

        $manager->flush();
    }

    protected function parse(array $array)
    {
        $dataTransformer = !empty($array[self::DATA_TRANSFORMER_ANNOTATION]) ? $array[self::DATA_TRANSFORMER_ANNOTATION] : null;
        unset($array[self::DATA_TRANSFORMER_ANNOTATION]);
        if ($dataTransformer !== null) {
            $dataTransformer = ($dataTransformer{0} === '@') ?
                $this->container->get(substr($dataTransformer, 1)) :
                new $dataTransformer()
            ;

            if (!($dataTransformer instanceof DataTransformerInterface)) {
                $class = get_class($dataTransformer);
                $expected = 'Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface';
                throw new \InvalidArgumentException("data transformer '$class' is not an instance of $expected");
            }
        }

        foreach ($array as $key => $value) {
            if (is_string($value) && preg_match('/^([@#])[^\1]/', $value)) {
                $substring = substr($value, 1);
                switch ($value{0}) {
                    case '@':
                        $array[$key] = $this->referenceRepository->getReference($substring);
                        break;
                    case '#':
                        $array[$key] = $this->container->getParameter($substring);
                        break;
                }
            } elseif (is_array($value)) {
                $array[$key] = $this->parse($value);
            }
        }

        if (!empty($dataTransformer)) {
            return $dataTransformer->transform($array);
        }

        return $array;
    }

    protected function getSame($object, array $equalCondition, ObjectManager $manager)
    {
        $conditions = array();
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($equalCondition as $property) {
            $conditions[$property] = $accessor->getValue($object, $property);
        }

        return $manager->getRepository(get_class($object))->findBy($conditions);
    }
}
