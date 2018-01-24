<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $admin;

    protected function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->router = $kernel->getContainer()->get('router');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $this->admin = new User();
        $this->admin->setEmail('admin@prono-bet.com')
            ->setRole('ROLE_ADMIN')
            ->setPlainPassword('123456')
            ->setNickname('admin');
        $password = $this->encoder->encodePassword($this->admin, $this->admin->getPlainPassword());
        $this->admin->setPassword($password);
        $this->em->persist($this->admin);
        $this->em->flush($this->admin);

        $this->client = $this->login();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->em->remove($this->admin);
        $this->em->flush($this->admin);
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->admin, $url, $this->translator);
        return $client;
    }

    public function testIndex()
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/admin/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains(
            'Tableau de bord',
            $client->getResponse()->getContent()
        );

    }

}
