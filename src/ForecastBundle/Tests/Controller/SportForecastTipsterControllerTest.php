<?php


namespace ForecastBundle\Tests\Controller;


use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Tests\Encoder\PasswordEncoder;

class SportForecastTipsterControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $tipsters;
    private $sportForecast;

    protected function setUp()
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

        $this->createTipsters($domain, $plainPassword, $this->em, $this->encoder);
        $this->createSportForecast($this->em);
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach($this->sportForecast->getSportBets() as $sportBet) {
            $sport = $sportBet->getSport();

            $this->em->remove($sportBet);
            $this->em->flush($sportBet);

            $this->em->remove($sport);
            $this->em->flush($sport);

        }

        $this->em->remove($this->sportForecast);
        $this->em->flush($this->sportForecast);

        foreach($this->tipsters as $tipster) {
            $user = $tipster->getUser();

            $this->em->remove($tipster);
            $this->em->remove($user);

            $this->em->flush();
        }
    }

    private function createTipsters($domain, $plainPassword, EntityManager $em, $encoder)
    {
        $users = [1, 2];
        foreach($users as $number) {
            $user = new User();
            $password = $encoder->encodePassword($user, $plainPassword);
            $user->setEmail("tipster$number@$domain")
                ->setNickname("tipster$number")
                ->setPassword($password)
                ->setRole('ROLE_TIPSTER')
                ->setPlainPassword($plainPassword);
            $em->persist($user);
            $em->flush($user);

            $tipster = new Tipster($user);
            $tipster->setFee(25)
                ->setCommission(20);

            $em->persist($tipster);
            $em->flush($tipster);

            $this->tipsters[] = $tipster;

        }
    }

    private function createSportForecast(EntityManager $em)
    {
        $sport = new Sport();
        $sport->setName('sport test')
            ->setVisible(true);
        $em->persist($sport);
        $em->flush($sport);

        $sportForecast = new SportForecast($this->tipsters[0]);
        $sportForecast->setTitle('sport forecast 1')
            ->setIsVip(true)
            ->setBetting(20);
        $em->persist($sportForecast);
        $em->flush($sportForecast);

        $sportBet = new SportBet($sportForecast);
        $sportBet->setWinner('sport bet winner 1')
            ->setRating(2.3)
            ->setSport($sport)
            ->setPlayedAt(new \DateTime());
        $em->persist($sportBet);
        $em->flush($sportBet);

        $sportForecast->addSportBet($sportBet);

        $this->sportForecast = $sportForecast;
    }

    private function login(User $user)
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($user, $url, $this->translator);
        return $client;
    }

    public function testShow()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testShowWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEdit()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDelete()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPublish()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPublishWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testAddSportBet()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_bet_add', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testAddSportBetWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_bet_add', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditSportBet()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $sportBet = $this->sportForecast->getSportBets()[0];

        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $sportBet->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditSportBetWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $sportBet = $this->sportForecast->getSportBets()[0];

        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $sportBet->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteSportBet()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $sportBet = $this->sportForecast->getSportBets()[0];

        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $sportBet->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteSportBetWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $sportBet = $this->sportForecast->getSportBets()[0];

        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $sportBet->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testSubmitChange()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_submit_change', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testSubmitChangeWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_submit_change', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditTicket()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit_ticket', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditTicketWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_edit_ticket', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testValidate()
    {
        $client = $this->login($this->tipsters[0]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testValidateWithOtherTipster()
    {
        $client = $this->login($this->tipsters[1]->getUser());

        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );
    }

}