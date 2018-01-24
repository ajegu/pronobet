<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookmakerControllerTest extends WebTestCase
{
    private $router;
    private $em;
    private $translator;
    private $user;
    private $bookmaker;

    protected function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->router = $kernel->getContainer()->get('router');
        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $encoder = $kernel->getContainer()->get('security.password_encoder');
        $this->translator = $kernel->getContainer()->get('translator');

        $user = $this->em->getRepository('AppBundle:User')
            ->findOneByEmail('admin@pronobet.local');
        if ($user !== null) {
            $this->em->remove($user);
            $this->em->flush($user);
        }

        $this->user = new User();
        $this->user->setEmail('admin@pronobet.local')
            ->setPlainPassword('123456')
            ->setRole('ROLE_ADMIN')
            ->setNickname('admin test');
        $encoded = $encoder->encodePassword($this->user, $this->user->getPlainPassword());
        $this->user->setPassword($encoded);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->bookmaker = new Bookmaker();
        $this->bookmaker->setName('bookmaker test')
            ->setWebsiteLink('www.bookmaker.test')
            ->setBonus(0)
            ->setVisible(true);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $user = $this->em->getRepository('AppBundle:User')
            ->findOneByEmail($this->user->getEmail());
        if ($user !== null) {
            $this->em->remove($user);
        }

        $bookmaker = $this->em->getRepository('AppBundle:Bookmaker')
            ->findOneByName($this->bookmaker->getName());
        if ($bookmaker !== null) {
            $this->em->remove($bookmaker);
        }

        $this->em->flush();
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->user, $url, $this->translator);
        return $client;
    }

    private function createBookmaker()
    {
        $this->em->persist($this->bookmaker);
        $this->em->flush($this->bookmaker);
    }

    public function testIndex()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_bookmaker_index');
        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testAdd()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_bookmaker_add');
        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['adminbundle_bookmaker[name]'] = $this->bookmaker->getName();
        $form['adminbundle_bookmaker[websiteLink]'] = $this->bookmaker->getWebsiteLink();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

    }

    public function testEdit()
    {
        $this->createBookmaker();
        $client = $this->login();
        $url = $this->router->generate('admin_bookmaker_edit', array(
            'id' => $this->bookmaker->getId()
        ));

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $this->bookmaker->setName('updated bookmaker');

        $form['adminbundle_bookmaker[name]'] = $this->bookmaker->getName();
        $form['adminbundle_bookmaker[websiteLink]'] = $this->bookmaker->getWebsiteLink();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDelete()
    {
        $this->createBookmaker();
        $client = $this->login();
        $url = $this->router->generate('admin_bookmaker_delete', array(
            'id' => $this->bookmaker->getId()
        ));

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.delete'))->form();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $bookmaker = $this->em->getRepository('AppBundle:Bookmaker')
            ->findOneByName($this->bookmaker->getName());

        $this->assertNull(
           $bookmaker
        );
    }
}
