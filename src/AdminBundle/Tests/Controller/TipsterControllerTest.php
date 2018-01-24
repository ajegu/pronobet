<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TipsterControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $admin;
    private $client;
    private $user;

    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $email = 'user1@test.local';
        $this->user = $this->em->getRepository('AppBundle:User')
            ->findOneByEmail($email);

        if ($this->user === null) {
            $this->user = new User();
            $this->user
                ->setEmail($email)
                ->setNickname('user1')
                ->setPlainPassword('123456')
                ->setPassword('123456');

            $this->em->persist($this->user);
            $this->em->flush($this->user);
        }

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

        $tipster = $this->em->getRepository('AppBundle:Tipster')
            ->findOneByUser($this->user);

        if ($tipster !== null) {

            foreach ($tipster->getSubscriptions() as $subscription) {
                $this->em->remove($subscription);
            }

            $this->em->remove($tipster);
            $this->em->flush();
        }

        $this->em->remove($this->user);
        $this->em->remove($this->admin);
        $this->em->flush();
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->admin, $url, $this->translator);
        return $client;
    }

    private function addTipster($fee)
    {
        $url = $this->router->generate('admin_tipster_add', ['id' => $this->user->getId()]);
        $crawler = $this->client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['adminbundle_tipster[fee]'] = $fee;
        $form['adminbundle_tipster[commission]'] = 20;

        $picture = new UploadedFile(
            __DIR__ . '/../Images/tipster_picture.png',
            'tipster_picture',
            'image/png',
            13940
        );

        $cover = new UploadedFile(
            __DIR__ . '/../Images/tipster_cover.jpg',
            'tipster_cover',
            'image/jpeg',
            67410
        );

        $form['adminbundle_tipster[pictureFile]'] = $picture;
        $form['adminbundle_tipster[coverFile]'] = $cover;

        $crawler = $this->client->submit($form);

        return $crawler;
    }


    public function testAdd()
    {

        $crawler = $this->addTipster(20);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->translator->trans('title.tipster_show'),
            $this->client->getResponse()->getContent()
        );
    }

    public function testAddWithErrors()
    {
        $crawler = $this->addTipster(0, 0);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }


    public function testEdit()
    {
        $this->addTipster(10, 10);

        $tipster = $this->em->getRepository('AppBundle:Tipster')
            ->findOneByUser($this->user);

        $url = $this->router->generate('admin_tipster_edit', ['id' => $tipster->getId()]);
        $crawler = $this->client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $form['adminbundle_tipster[fee]'] = 25;

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->translator->trans('title.tipster_show'),
            $this->client->getResponse()->getContent()
        );

    }

    public function testDelete()
    {
        $this->addTipster(10, 10);

        $tipster = $this->em->getRepository('AppBundle:Tipster')
            ->findOneByUser($this->user);

        $url = $this->router->generate('admin_tipster_delete', ['id' => $tipster->getId()]);
        $crawler = $this->client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.delete'))->form();

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            302,
            $this->client->getResponse()->getStatusCode()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->translator->trans('text.tipster_delete_success'),
            $this->client->getResponse()->getContent()
        );
    }

}
