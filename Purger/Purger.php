<?php
namespace Pecserke\YamlFixturesBundle\Purger;

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PHPCRPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager as MongoDbDocumentManager;
use Doctrine\ODM\PHPCR\DocumentManager as PhpCrDocumentManager;
use Doctrine\ORM\EntityManager;

class Purger implements PurgerInterface
{
    /**
     * @var ManagerRegistry[]
     */
    protected $registries;

    public function addRegistry(ManagerRegistry $registry)
    {
        $this->registries[] = $registry;
    }

    /**
     * @param bool $truncate
     */
    public function purge($truncate = false)
    {
        $ormPurger = new ORMPurger();
        if ($truncate) {
            $ormPurger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        }
        $mongodbPurger = new MongoDBPurger();
        $phpcrPurger = new PHPCRPurger();

        foreach ($this->registries as $registry) {
            foreach ($registry->getManagers() as $manager) {
                if ($manager instanceof EntityManager) {
                    $ormPurger->setEntityManager($manager);
                    $ormPurger->purge();
                } else if ($manager instanceof MongoDbDocumentManager) {
                    $mongodbPurger->setDocumentManager($manager);
                    $mongodbPurger->purge();
                } else if ($manager instanceof PhpCrDocumentManager) {
                    $phpcrPurger->setDocumentManager($manager);
                    $phpcrPurger->purge();
                } else {
                    throw new \UnexpectedValueException('unsupported ObjectManager');
                }
            }
        }
    }
} 
