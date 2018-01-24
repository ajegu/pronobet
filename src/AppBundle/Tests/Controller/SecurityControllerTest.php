<?php


namespace AppBundle\Tests\Controller;


use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
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
            ->setPlainPassword('123456')
            ->setResetPasswordToken('secrettoken');
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

    public static function login(User $user, $url, $translator)
    {
        $client = static::createClient();
        $client->request('GET', '/logout');
        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton($translator->trans('button.login'))->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = $user->getPlainPassword();

        $client->submit($form);

        return $client;
    }

    public function testLoginIndex()
    {
        $url = $this->router->generate('login');
        $client = static::createClient();

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testLogin()
    {
        $this->createUser();
        $url = $this->router->generate('login');

        $client = static::login($this->user, $url, $this->translator);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

    }

    public function testLoginWithBadCreditential()
    {
        $this->createUser();
        $url = $this->router->generate('login');

        $this->user->setPlainPassword('654321');

        $client = static::login($this->user, $url, $this->translator);

        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.error')->count()
        );
    }

    public function testLogout()
    {
        $this->createUser();
        $url = $this->router->generate('login');

        $client = static::login($this->user, $url, $this->translator);

        $url = $this->router->generate('logout');

        $client->request('GET', $url);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testResetPassword()
    {
        $this->createUser();
        $url = $this->router->generate('login');
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('label.forget_password'))->link();
        $crawler = $client->click($link);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.reset'))->form();

        $form['user_email[email]'] = $this->user->getEmail();
        $crawler = $client->submit($form);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testResetPasswordWithError()
    {
        $this->createUser();
        $url = $this->router->generate('login');
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('label.forget_password'))->link();
        $crawler = $client->click($link);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.reset'))->form();

        $form['user_email[email]'] = 'email@not.exist';
        $crawler = $client->submit($form);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertGreaterThan(
            0,
            $crawler->filter('.negative')->count()
        );
    }

    public function testConfirmResetPasswordWithError()
    {
        $this->createUser();
        $url = $this->router->generate('confirm_reset_password', ['token' => 'faketoken']);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.negative')->count()
        );
    }

    public function testConfirmResetPassword()
    {
        $this->createUser();
        $url = $this->router->generate('confirm_reset_password', ['token' => $this->user->getResetPasswordToken()]);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );

        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();

        $this->user->setPlainPassword('654321');

        $form['reset_password[password]'] = $this->user->getPlainPassword();
        $form['reset_password[repeatPassword]'] = $this->user->getPlainPassword();

        $crawler = $client->submit($form);

        $this->assertEquals(
            0,
            $crawler->filter('.negative')->count()
        );

        $url = $this->router->generate('login');

        $client = static::login($this->user, $url, $this->translator);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }
}