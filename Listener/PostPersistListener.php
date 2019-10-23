<?php

/*
 * This file is part of the YamlFixturesBundle package.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Listener;

use InvalidArgumentException;
use Pecserke\YamlFixturesBundle\DataFixtures\FixtureObjectArrayDataEvaluator;
use Pecserke\YamlFixturesBundle\Event\PostPersistEvent;

class PostPersistListener {
    /**
     * @var FixtureObjectArrayDataEvaluator
     */
    private $evaluator;

    public function __construct(FixtureObjectArrayDataEvaluator $evaluator) {
        $this->evaluator = $evaluator;
    }

    public function postPersist(PostPersistEvent $event) {
        $postPersist = $event->getPostPersist();
        if (empty($postPersist)) {
            return;
        }

        $referenceName = $event->getReferenceName();

        if (!is_array($postPersist)) {
            throw new InvalidArgumentException(sprintf(
                'invalid postPersist callback at "%s": array [$object, $method, $params] expected, %s given',
                $referenceName,
                gettype($postPersist)
            ));
        }
        $postPersist = $this->evaluator->evaluate($postPersist);
        if (!is_object($postPersist[0])) {
            throw new InvalidArgumentException(sprintf(
                'invalid postPersist callback at "%s": argument 1: object expected, %s given',
                $referenceName,
                gettype($postPersist[0])
            ));
        }
        if (!method_exists($postPersist[0], $postPersist[1])) {
            throw new InvalidArgumentException(sprintf(
                'invalid postPersist callback at "%s": method %s::%s does not exist',
                $referenceName,
                get_class($postPersist[0]),
                $postPersist[1]
            ));
        }

        call_user_func_array(array($postPersist[0], $postPersist[1]), $postPersist[2]);
    }
}
