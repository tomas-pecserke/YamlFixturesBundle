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

use Doctrine\Common\Persistence\ObjectManager;
use Pecserke\YamlFixturesBundle\DataTransformer\ObjectTransformerInterface;
use Pecserke\YamlFixturesBundle\Event\Events;
use Pecserke\YamlFixturesBundle\Event\PostPersistEvent;
use Pecserke\YamlFixturesBundle\Listener\PostPersistListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayFixturesLoader {
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var FixtureObjectArrayDataEvaluator
     */
    private $evaluator;

    public function __construct(FixtureObjectArrayDataEvaluator $evaluator) {
        $this->evaluator = $evaluator;
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(Events::POST_PERSIST, [new PostPersistListener($evaluator), 'postPersist']);
    }

    public function load(array $fixture, ObjectManager $manager): void {
        $transformerDefinition = $fixture['transformer'] ?? null;
        $transformer = $this->evaluator->resolveObjectTransformer($transformerDefinition);

        $class = $fixture['class'];
        $equalCondition = $fixture['equal_condition'] ?? null;
        foreach ($fixture['data'] as $referenceName => $data) {
            $this->loadFixtureObject($manager, $transformer, $referenceName, $class, $data, $equalCondition);
        }

        $manager->flush();
    }

    protected function loadFixtureObject(
        ObjectManager $manager,
        ObjectTransformerInterface $transformer,
        $referenceName,
        $className,
        array $data,
        $equalCondition
    ): void {
        $postPersist = $data[FixtureObjectArrayDataEvaluator::POST_PERSIST_ANNOTATION] ?? null;
        unset($data[FixtureObjectArrayDataEvaluator::POST_PERSIST_ANNOTATION]);

        $data = $this->evaluator->evaluate($data);
        $object = $transformer->transform($data, $className);

        $referenceRepository = $this->evaluator->getReferenceRepository();
        if (!empty($equalCondition)) {
            $result = $this->getSame($object, $equalCondition, $manager);
            if (count($result) > 0) {
                $referenceRepository->addReference($referenceName, array_shift($result));
                return;
            }
        }

        $manager->persist($object);
        $referenceRepository->addReference($referenceName, $object);
        $this->eventDispatcher->dispatch(
            new PostPersistEvent($object, $referenceName, $postPersist),
            Events::POST_PERSIST
        );
    }

    protected function getSame($object, array $equalCondition, ObjectManager $manager): array {
        $conditions = array();
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($equalCondition as $property) {
            $conditions[$property] = $accessor->getValue($object, $property);
        }

        return $manager->getRepository(get_class($object))->findBy($conditions);
    }
}
