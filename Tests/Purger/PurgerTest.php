<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Tests\Purger;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Pecserke\YamlFixturesBundle\Purger\Purger;

class PurgerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    private $manager;

    /**
     * @var Purger
     */
    private $purger;

    protected function setUp()
    {
        $this->registry = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;

        $self = $this;

        $this->registry
            ->expects($this->any())
            ->method('getManagers')
            ->willReturnCallback(function() use ($self) {
                return array($self->manager);
            })
        ;

        $this->purger = new Purger();
        $this->purger->addRegistry($this->registry);
    }

    public function testPurgeOrm()
    {
        $this->manager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $factory = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->manager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($factory)
        ;

        $factory
            ->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn(array())
        ;

        $connection = $this
            ->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->manager
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $this->purger->purge();
    }

    public function testMongoDb()
    {
        $this->manager = $this
            ->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $factory = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->manager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($factory)
        ;

        $factory
            ->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn(array())
        ;

        $this->purger->purge();
    }

    public function testPhpCr()
    {
        $this->manager = $this
            ->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $session = $this
            ->getMockBuilder('PHPCR\SessionInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $this->manager
            ->expects($this->once())
            ->method('getPhpcrSession')
            ->willReturn($session)
        ;

        $root = $this
            ->getMockBuilder('PHPCR\NodeInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
        $session
            ->expects($this->once())
            ->method('getRootNode')
            ->willReturn($root)
        ;

        $root
            ->expects($this->once())
            ->method('getProperties')
            ->willReturn(array())
        ;
        $root
            ->expects($this->once())
            ->method('getNodes')
            ->willReturn(array())
        ;

        $this->purger->purge();
    }
}
