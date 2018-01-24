<?php


namespace AdminBundle\Tests\Repository;


use AppBundle\Entity\Sport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SportRepositoryTest extends KernelTestCase
{
    private $em;
    private $sport;

    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->sport = new Sport();
        $this->sport
            ->setName('sport test')
            ->setVisible(true)
            ->setIcon('/path/to/icon');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }

    public function testAdd()
    {
        $this->em->persist($this->sport);
        $this->em->flush($this->sport);

        $sport = $this->em->getRepository('AppBundle:Sport')->find($this->sport->getId());

        $this->assertEquals(
            $this->sport->getId(),
            $sport->getId()
        );
    }

    public function testDelete()
    {
        $sport = $this->em->getRepository('AppBundle:Sport')
            ->findOneByName($this->sport->getName());

        $this->em->remove($sport);
        $this->em->flush($sport);

        $sports = $this->em->getRepository('AppBundle:Sport')
            ->findByName($this->sport->getName());

        $this->assertEquals(
            0,
            count($sports)
        );
    }

}