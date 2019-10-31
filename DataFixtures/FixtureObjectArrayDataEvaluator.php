<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\Transformer\ObjectTransformerInterface;
use Pecserke\YamlFixturesBundle\Transformer\PropertyValueTransformerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixtureObjectArrayDataEvaluator implements ContainerAwareInterface {
    /**
     * @var string
     */
    public const POST_PERSIST_ANNOTATION = '@postPersist';

    /**
     * @var string
     */
    public const DATA_TRANSFORMER_ANNOTATION = '@dataTransformer';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ReferenceRepository
     */
    private $referenceRepository;

    public function setContainer(ContainerInterface $container = null): void {
        $this->container = $container;
    }

    /**
     * @return ReferenceRepository
     */
    public function getReferenceRepository(): ReferenceRepository {
        return $this->referenceRepository;
    }

    public function setReferenceRepository(ReferenceRepository $referenceRepository): void {
        $this->referenceRepository = $referenceRepository;
    }

    public function evaluate(array $array) {
        $dataTransformer = !empty($array[self::DATA_TRANSFORMER_ANNOTATION])
            ? $array[self::DATA_TRANSFORMER_ANNOTATION]
            : null;
        unset($array[self::DATA_TRANSFORMER_ANNOTATION]);
        if ($dataTransformer !== null) {
            $dataTransformer = ($dataTransformer{0} === '@') ?
                $this->container->get(substr($dataTransformer, 1)) :
                new $dataTransformer();

            if (!($dataTransformer instanceof PropertyValueTransformerInterface)) {
                $class = get_class($dataTransformer);
                $expected = 'Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface';
                throw new InvalidArgumentException("data transformer '$class' is not an instance of $expected");
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
                $array[$key] = $this->evaluate($value);
            }
        }

        if (!empty($dataTransformer)) {
            return $dataTransformer->transform($array);
        }

        return $array;
    }

    /**
     * @param string $transformerDefinition
     * @return ObjectTransformerInterface
     */
    public function resolveObjectTransformer($transformerDefinition): ObjectTransformerInterface {
        if (!empty($transformerDefinition)) {
            $transformer = strpos($transformerDefinition, '@') === 0
                ? $this->container->get(substr($transformerDefinition, 1))
                : new $transformerDefinition();
        }
        $transformer = $transformer ?? $this->container->get('pecserke_fixtures.object_transformer.default');
        if (!($transformer instanceof ObjectTransformerInterface)) {
            $class = get_class($transformer);
            $expected = 'Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface';
            throw new InvalidArgumentException("data transformer '$class' is not an instance of $expected");
        }

        return $transformer;
    }
}
