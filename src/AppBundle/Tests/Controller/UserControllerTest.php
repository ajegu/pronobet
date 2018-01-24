<?php


namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Subscriber;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $user;

    protected function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $this->user = new User();
        $this->user
            ->setEmail('user@test.local')
            ->setNickname('test user')
            ->setPlainPassword('123456');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $user = $this->em->getRepository('AppBundle:User')
            ->findOneByEmail($this->user->getEmail());

        if ($user !== null) {
            $this->em->remove($user);
            $this->em->flush($user);
        }
    }

    private function createUser()
    {
        $encoded = $this->encoder->encodePassword($this->user, $this->user->getPlainPassword());
        $this->user->setPassword($encoded);
        $this->em->persist($this->user);
        $this->em->flush($this->user);
    }

    private function createSubscriber()
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($this->user->getEmail())
            ->setPartners(true);
        $this->em->persist($subscriber);
        $this->em->flush();
    }

    public function testNew()
    {
        $client = static::createClient();
        $url = $this->router->generate('user_new');

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.create'))->form();
        $form['appbundle_user[email]'] = $this->user->getEmail();
        $form['appbundle_user[nickname]'] = $this->user->getNickname();
        $form['appbundle_user[plainPassword]'] = $this->user->getPlainPassword();
        $form['appbundle_user[termsAccepted]'] = 1;

        $crawler = $client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testNewWithErrors()
    {
        $client = static::createClient();
        $url = $this->router->generate('user_new');

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.create'))->form();
        $form['appbundle_user[email]'] = $this->user->getEmail();
        $form['appbundle_user[nickname]'] = $this->user->getNickname();
        $form['appbundle_user[plainPassword]'] = '123';

        $crawler = $client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testNewWithExistingValue()
    {
        $this->createUser();

        $client = static::createClient();
        $url = $this->router->generate('user_new');

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.create'))->form();
        $form['appbundle_user[email]'] = $this->user->getEmail();
        $form['appbundle_user[nickname]'] = $this->user->getNickname();
        $form['appbundle_user[plainPassword]'] = $this->user->getPlainPassword();

        $crawler = $client->submit($form);

        $this->assertGreaterThan(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testConfirmEmail()
    {
        $this->createUser();
        $url = $this->router->generate('user_confirm_email', [
            'id' => $this->user->getId(),
            'email' => $this->user->getEmail()
        ]);

        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('message.confirm_email_success'),
            $client->getResponse()->getContent()
        );
    }

    public function testConfirmEmailWithError()
    {
        $this->createUser();
        $url = $this->router->generate('user_confirm_email', [
            'id' => $this->user->getId(),
            'email' => 'bad email'
        ]);

        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(
            404,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testShow()
    {
        $this->createUser();
        $url = $this->router->generate('login');

        $client = SecurityControllerTest::login($this->user, $url, $this->translator);

        $url = $this->router->generate('user_show', ['id' => $this->user->getId()]);
        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('title.user_profile'),
            $client->getResponse()->getContent()
        );
    }

    public function testEdit()
    {
        $this->createUser();
        $url = $this->router->generate('login');

        $client = SecurityControllerTest::login($this->user, $url, $this->translator);

        $url = $this->router->generate('user_show', ['id' => $this->user->getId()]);
        $crawler = $client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('button.edit_info'))->link();

        $crawler = $client->click($link);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('title.user_edit'),
            $client->getResponse()->getContent()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit_info'))->form();

        $this->user->setEmail('user-updated@test.local')
            ->setNickname('user updated')
            ->setPassword('143256');

        $form['appbundle_user[email]'] = $this->user->getEmail();
        $form['appbundle_user[nickname]'] = $this->user->getNickname();
        $form['appbundle_user[plainPassword]'] = $this->user->getPlainPassword();

        $crawler = $client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testNewWithNewsletterSubscription()
    {
        $client = static::createClient();
        $url = $this->router->generate('user_new');

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.create'))->form();
        $form['appbundle_user[email]'] = $this->user->getEmail();
        $form['appbundle_user[nickname]'] = $this->user->getNickname();
        $form['appbundle_user[plainPassword]'] = $this->user->getPlainPassword();
        $form['appbundle_user[termsAccepted]'] = 1;
        $form['appbundle_user[confirm]'] = 1;
        $form['appbundle_user[partners]'] = 1;

        $crawler = $client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );

        $subscriber = $this->em->getRepository('AppBundle:Subscriber')
            ->findOneByEmail($this->user->getEmail());

        $this->assertNotEquals(null, $subscriber);

        $this->assertTrue($subscriber->getPartners());

        $this->em->remove($subscriber);
        $this->em->flush();
    }

    public function testConfirmEmailWithNewsletterSubscription()
    {
        $this->createUser();
        $this->createSubscriber();

        $url = $this->router->generate('user_confirm_email', [
            'id' => $this->user->getId(),
            'email' => $this->user->getEmail()
        ]);

        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('message.confirm_email_success'),
            $client->getResponse()->getContent()
        );

        $subscribers = $this->em->getRepository('AppBundle:Subscriber')
            ->findByEmailValid(true);

        $find = false;
        $subscriber = null;
        foreach ($subscribers as $sub) {
            if ($sub->getEmail() === $this->user->getEmail()) {
                $subscriber = $sub;
                $find = true;
                break;
            }
        }

        $this->assertTrue($find);

        if ($subscriber !== null) {
            $this->em->remove($subscriber);
            $this->em->flush();
        }
    }
}