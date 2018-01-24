<?php


namespace AdminBundle\Tests\Repository;


use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TipsterRepositoryTest extends KernelTestCase
{
    private $em;

    private $tipster;

    public function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $user = new User();
        $user
            ->setEmail('user1@test.local')
            ->setNickname('user1')
            ->setPassword('123456')
            ->setRole('ROLE_MEMBER')
            ->setPhoneNumber('0609080706')
            ->setEmailValid(true);

        $this->tipster = new Tipster($user);
        $this->tipster
            ->setFee(25)
            ->setCommission(20);
    }

    public function testAdd()
    {
        $user = $this->tipster->getUser();
        $this->em->persist($user);
        $this->em->flush($user);

        $this->tipster->setUser($user);

        $this->em->persist($this->tipster);
        $this->em->flush($this->tipster);

        $tipster = $this->em->getRepository('AppBundle:Tipster')->find($this->tipster->getId());

        $this->assertEquals($tipster->getId(), $this->tipster->getId());
    }

    public function testDelete()
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneByEmail($this->tipster->getUser()->getEmail());

        //$tipster = $this->em->getRepository('AppBundle:Tipster')->findOneByUser($user);

        $tipster = $user->getTipster();
        $id = $tipster->getId();


        $this->em->remove($tipster);
        $this->em->flush($tipster);

        $tipster = $this->em->getRepository('AppBundle:Tipster')->find($id);
        $this->assertEquals(null, $tipster);

        $this->em->remove($user);
        $this->em->flush($user);

        $user = $this->em->getRepository('AppBundle:User')->findOneByEmail($this->tipster->getUser()->getEmail());

        $this->assertEquals(null, $user);
    }

}