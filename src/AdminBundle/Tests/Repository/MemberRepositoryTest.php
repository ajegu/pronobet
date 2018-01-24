<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 12/04/17
 * Time: 11:45
 */

namespace AdminBundle\Tests\Repository;


use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MemberRepositoryTest extends KernelTestCase
{
    private $em;
    private $encoder;
    private $member;

    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->encoder = static::$kernel->getContainer()
            ->get('security.password_encoder');

        $this->member = new User();
        $this->member
            ->setEmail('user1@test.local')
            ->setNickname('user1')
            ->setPlainPassword('123456')
            ->setRole('ROLE_MEMBER')
            ->setPhoneNumber('0609080706')
            ->setEmailValid(true);

    }

    public function testAdd()
    {
        $password = $this->encoder->encodePassword($this->member, $this->member->getEmail());
        $this->member->setPassword($password);

        $this->em->persist($this->member);
        $this->em->flush($this->member);

        $this->assertEquals(1, $this->getMemberCount($this->member->getEmail()));
    }

    private function getMemberCount($search)
    {
        $result = $this->em
            ->getRepository('AppBundle:User')
            ->getMembers(0, 10, $search);

        return $result->count();
    }

    public function testDelete()
    {

        $members = $this->em->getRepository('AppBundle:User')
            ->findByEmail($this->member->getEmail());

        $member = $members[0];

        $this->em->remove($member);
        $this->em->flush($member);

        $this->assertEquals(0, $this->getMemberCount($this->member->getEmail()));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }
}