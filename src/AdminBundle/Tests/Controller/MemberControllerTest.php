<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AdminBundle\Tests\Controller\DefaultControllerTest;


class MemberControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $client;
    private $admin;
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
        $this->user = new User();
        $this->user
            ->setEmail('user1@test.local')
            ->setNickname('user1')
            ->setPlainPassword('123456');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $users = $this->em->getRepository('AppBundle:User')
            ->findAll();

        foreach ($users as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->admin, $url, $this->translator);
        return $client;
    }

    private function createUser()
    {
        $encoded = $this->encoder->encodePassword($this->user, $this->user->getPlainPassword());
        $this->user->setPassword($encoded);
        $this->em->persist($this->user);
        $this->em->flush($this->user);
    }

    public function testIndex()
    {
        $this->client->request('GET', '/admin/member');
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testAdd()
    {
        $url = $this->router->generate('admin_member_index');
        $crawler = $this->client->request('GET', $url);
        $link = $crawler->selectLink($this->translator->trans('button.add'))->link();

        $crawler = $this->client->click($link);

        $this->assertContains(
            $this->translator->trans('title.member_add'),
            $this->client->getResponse()->getContent()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['adminbundle_user[nickname]'] = $this->user->getNickname();
        $form['adminbundle_user[email]'] = $this->user->getEmail();
        $form['adminbundle_user[plainPassword]'] = $this->user->getPlainPassword();

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $url = $this->router->generate('admin_member_index', ['search' => $this->user->getEmail()]);
        $crawler = $this->client->request('GET', $url );

        $this->assertContains(
            $this->user->getEmail(),
            $this->client->getResponse()->getContent()
        );
    }

    public function testAddWithExistingEmail()
    {
        $this->createUser();
        $url = $this->router->generate('admin_member_add');
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['adminbundle_user[nickname]'] = 'test2';
        $form['adminbundle_user[email]'] = $this->user->getEmail();
        $form['adminbundle_user[plainPassword]'] = 'test123';

        $crawler = $this->client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }

    public function testAddWithInvalidEmail()
    {
        $url = $this->router->generate('admin_member_add');
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['adminbundle_user[nickname]'] = 'test 3';
        $form['adminbundle_user[email]'] = 'test.phpunit.fr';
        $form['adminbundle_user[plainPassword]'] = 'test123';

        $crawler = $this->client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }

    public function testAddWithWrongPassword()
    {
        $url = $this->router->generate('admin_member_add');
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['adminbundle_user[nickname]'] = 'test 4';
        $form['adminbundle_user[email]'] = 'test_password@phpunit.fr';
        $form['adminbundle_user[plainPassword]'] = 'test';

        $crawler = $this->client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }

    public function testEdit()
    {
        $this->createUser();
        $url = $this->router->generate('admin_member_index', ['search' => $this->user->getEmail()]);
        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.edit'))->link();
        $crawler = $this->client->click($link);
        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $this->user->setEmail('user1-update@test.local')
            ->setNickname('user1 updated');

        $form['adminbundle_user[email]'] = $this->user->getEmail();
        $form['adminbundle_user[nickname]'] = $this->user->getNickname();
        $form['adminbundle_user[plainPassword]'] = $this->user->getPlainPassword();

        $crawler = $this->client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('div.negative')->count()
        );

        $url = $this->router->generate('admin_member_index', ['search' => $this->user->getEmail()]);
        $this->client->request('GET', $url);

        $this->assertContains(
            $this->user->getEmail(),
            $this->client->getResponse()->getContent()
        );
    }
/*
    public function testEditWithExistingEmail()
    {
        $this->createUser();
        $url = $this->router->generate('admin_member_index', ['search' => $this->user->getEmail()]);
        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.edit'))->link();
        $crawler = $this->client->click($link);
        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $form['adminbundle_user[email]'] = $container = $this->client->getKernel()->getContainer()->getParameter('test_admin_account_email');
        $form['adminbundle_user[nickname]'] = 'test 5';

        $crawler = $this->client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('div.negative')->count()
        );
    }
*/
    public function testDelete()
    {
        $this->createUser();
        $url = $this->router->generate('admin_member_index', ['search' => $this->user->getEmail()]);
        $crawler = $this->client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.delete'))->link();
        $crawler = $this->client->click($link);

        $form = $crawler->selectButton($this->translator->trans('button.delete'))->form();
        $this->client->submit($form);

        $this->assertEquals(
            302,
            $this->client->getResponse()->getStatusCode()
        );

        $this->client->followRedirect();

        $this->assertContains(
            $this->translator->trans('text.member_delete_success'),
            $this->client->getResponse()->getContent()
        );
    }

    public function oldtestUpgradeToTipster()
    {
        $crawler = $this->client->request('GET', '/admin/member');

        $link = $crawler->selectLink('Voir')->link();
        $crawler = $this->client->click($link);
        $link = $crawler->selectLink('Promouvoir en Tipster')->link();

        $crawler = $this->client->click($link);

        $this->assertContains(
            'Promouvoir le membre en Tipster',
            $crawler->filter('h2')->text()
        );

        $link = $crawler->selectLink('Promouvoir en Tipster')->link();
        $crawler = $this->client->click($link);

        $this->assertContains(
            'Créer le nouveau tipster',
            $crawler->filter('h2')->text()
        );


    }

}
