<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\Championship;
use AppBundle\Entity\Sport;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChampionshipControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $admin;

    private $sport;
    private $championship;

    protected function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->router = $kernel->getContainer()->get('router');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        // Create sport test.
        $this->sport = new Sport();
        $this->sport
            ->setName('sport test')
            ->setVisible(true);

        $this->em->persist($this->sport);
        $this->em->flush($this->sport);

        $this->championship = new Championship($this->sport);
        $this->championship
            ->setName('championship test')
            ->setVisible(true);

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

        $championship = $this->em->getRepository('AppBundle:Championship')
            ->findOneByName($this->championship->getName());

        if ($championship !== null) {
            $this->em->remove($championship);
            $this->em->flush($championship);
        }

        $this->em->remove($this->sport);
        $this->em->flush($this->sport);

        $this->em->remove($this->admin);
        $this->em->flush($this->admin);
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->admin, $url, $this->translator);
        return $client;
    }

    private function createChampionship()
    {
        $this->em->persist($this->championship);
        $this->em->flush($this->championship);
    }

    public function testIndex()
    {
        $url = $this->router->generate('admin_championship_index', ['id' => $this->sport->getId()]);

        $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testAdd()
    {
        $url = $this->router->generate('admin_championship_add', ['id' => $this->sport->getId()]);
        $crawler = $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['adminbundle_championship[name]'] = $this->championship->getName();
        $form['adminbundle_championship[visible]'] = $this->championship->getVisible();

        $this->client->submit($form);

        $this->assertEquals(
            302,
            $this->client->getResponse()->getStatusCode()
        );

    }

    public function testAddWithError()
    {
        $this->createChampionship();

        $url = $this->router->generate('admin_championship_add', ['id' => $this->sport->getId()]);
        $crawler = $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['adminbundle_championship[name]'] = $this->championship->getName();
        $form['adminbundle_championship[visible]'] = $this->championship->getVisible();

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            1,
            $crawler->filter('.negative')->count()
        );
    }

    public function testEdit()
    {
        $this->createChampionship();
        $url = $this->router->generate('admin_championship_edit', ['id' => $this->championship->getId()]);
        $crawler = $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();
        $this->championship->setName('updated championsip');
        $form['adminbundle_championship[name]'] = $this->championship->getName();

        $this->client->submit($form);

        $this->assertEquals(
            302,
            $this->client->getResponse()->getStatusCode()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->championship->getName(),
            $this->client->getResponse()->getContent()
        );
    }

    public function testDelete()
    {
        $this->createChampionship();
        $url = $this->router->generate('admin_championship_delete', ['id' => $this->championship->getId()]);
        $crawler = $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.delete'))->form();

        $this->client->submit($form);

        $this->assertEquals(
            302,
            $this->client->getResponse()->getStatusCode()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->translator->trans('text.championship_delete_success'),
            $this->client->getResponse()->getContent()
        );
    }
}
