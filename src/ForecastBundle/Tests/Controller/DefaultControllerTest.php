<?php


namespace ForecastBundle\Tests\Controller;


use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $tipster;

    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $domain = $kernel->getContainer()->getParameter('domain_name');
        $plainPassword = $kernel->getContainer()->getParameter('test_password');

        $user = new User();
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user
            ->setEmail('tipster@' . $domain)
            ->setNickname('tipster')
            ->setRole('ROLE_TIPSTER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);

        $this->em->persist($user);
        $this->em->flush();

        $this->tipster = new Tipster($user);
        $this->tipster->setFee(25)
            ->setCommission(20);

        $this->em->persist($this->tipster);
        $this->em->flush();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $user = $this->tipster->getUser();

        $this->em->remove($this->tipster);
        $this->em->remove($user);

        $this->em->flush();
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    public function testIndexWithUnauthenticateUser()
    {
        $url = $this->router->generate('forecast_index');
        $client = static::createClient();

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            'login',
            $client->getResponse()->getContent()
        );
    }

    public function testIndex()
    {
        $client = $this->login();

        $url = $this->router->generate('forecast_index');

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('title.dashboard'),
            $client->getResponse()->getContent()
        );
    }
}