<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\Sport;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SportControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $admin;
    private $sport;
    private $sportName = 'sport test';

    public function setUp()
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

    public function tearDown()
    {
        parent::tearDown();

        if ($this->sport !== null) {
            $this->em->remove($this->sport);
            $this->em->flush($this->sport);
        }

        $this->em->remove($this->admin);
        $this->em->flush($this->admin);
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->admin, $url, $this->translator);
        return $client;
    }

    private function addSport()
    {
        $this->sport = $this->em->getRepository('AppBundle:Sport')
            ->findOneByName($this->sportName);

        if ($this->sport === null) {
            $this->sport = new Sport();
            $this->sport
                ->setName($this->sportName)
                ->setIcon('/path/to/icon')
                ->setVisible(true);

            $this->em->persist($this->sport);
            $this->em->flush($this->sport);
        }
    }

    public function testIndex()
    {
        $this->addSport();

        $url = $this->router->generate('admin_sport_index');
        $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->sport->getName(),
            $this->client->getResponse()->getContent()
        );
    }

    public function testShow()
    {
        $this->addSport();

        $url = $this->router->generate('admin_sport_show', ['id' => $this->sport->getId()]);

        $this->client->request('GET', $url);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->sport->getName(),
            $this->client->getResponse()->getContent()
        );
    }

    public function testAdd()
    {
        $url = $this->router->generate('admin_sport_index');
        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.add'))->link();

        $crawler = $this->client->click($link);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['adminbundle_sport[name]'] = $this->sportName;

        $icon = new UploadedFile(
            __DIR__ . '/../Images/sport_icon.png',
            'sport_icon',
            'image/png',
            7530
        );
        $form['adminbundle_sport[iconFile]'] = $icon;

        $form['adminbundle_sport[visible]'] = true;

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $this->client->followRedirect();

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->sportName,
            $this->client->getResponse()->getContent()
        );
    }

    public function testAddWithExistingName()
    {
        $this->addSport();

        $url = $this->router->generate('admin_sport_add');

        $crawler = $this->client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['adminbundle_sport[name]'] = $this->sportName;

        $crawler = $this->client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }

    public function testEdit()
    {
        $this->addSport();

        $url = $this->router->generate('admin_sport_index', ['search' => $this->sport->getName()]);

        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.edit'))->link();

        $crawler = $this->client->click($link);

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();
        $nameUpdated = 'updated sport name';
        $form['adminbundle_sport[name]'] = $nameUpdated;

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $this->client->followRedirect();

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $nameUpdated,
            $this->client->getResponse()->getContent()
        );
    }

    public function testDelete()
    {
        $this->addSport();

        $url = $this->router->generate('admin_sport_index', ['search' => $this->sport->getName()]);

        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.delete'))->link();

        $crawler = $this->client->click($link);

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
            $this->translator->trans('text.sport_delete_success'),
            $this->client->getResponse()->getContent()
        );

    }
}
