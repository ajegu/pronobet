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

class SportForecastEditableControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $tipster;
    private $sportForecasts;

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

        $this->createTipster($domain, $plainPassword, $this->em, $this->encoder);
        $this->createSportForecasts($this->em);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $sport = null;

        foreach ($this->sportForecasts as $sportForecast) {
            foreach ($sportForecast->getSportBets() as $sportBet) {
                if ($sport == null) {
                    $sport = $sportBet->getSport();
                }

                $this->em->remove($sportBet);
                $this->em->flush($sportBet);
            }
            $this->em->remove($sportForecast);
            $this->em->flush($sportForecast);
        }

        $this->em->remove($sport);
        $this->em->flush($sport);

        $user = $this->tipster->getUser();

        $this->em->remove($this->tipster);
        $this->em->remove($user);

        $this->em->flush();
    }

    private function createTipster($domain, $plainPassword, EntityManager $em, $encoder)
    {
        $user = new User();
        $password = $encoder->encodePassword($user, $plainPassword);
        $user->setEmail("tipster@$domain")
            ->setNickname("tipster")
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

        $this->tipster = $tipster;
    }

    private function createSportForecasts(EntityManager $em)
    {
        $sport = new Sport();
        $sport->setName('sport test')
            ->setVisible(true);
        $em->persist($sport);
        $em->flush($sport);

        // Unpublished sport forecast
        $sfUnpublished = new SportForecast($this->tipster);
        $sfUnpublished->setTitle('sport forecast unpublished')
            ->setBetting(20)
            ->setIsVip(false);
        $em->persist($sfUnpublished);
        $em->flush($sfUnpublished);

        $unpublishedPlayedAt = new \DateTime();
        $unpublishedPlayedAt->add(new \DateInterval('P1D'));
        $sbUnpublished = new SportBet($sfUnpublished);
        $sbUnpublished->setWinner('winner unpublished')
            ->setRating(2.5)
            ->setPlayedAt($unpublishedPlayedAt)
            ->setSport($sport);
        $em->persist($sbUnpublished);
        $em->flush($sbUnpublished);

        $sfUnpublished->addSportBet($sbUnpublished);
        $this->sportForecasts['unpublished'] = $sfUnpublished;

        // In Progress sport forecast
        $sfInProgress = new SportForecast($this->tipster);
        $sfInProgress->setTitle('sport forecast in progress')
            ->setBetting(20)
            ->setIsVip(true)
            ->setPublishedAt(new \DateTime());
        $em->persist($sfInProgress);
        $em->flush($sfInProgress);

        $playedAt = new \DateTime();
        $interval = new \DateInterval('P1D');
        $playedAt->add($interval);
        $sbInProgress = new SportBet($sfInProgress);
        $sbInProgress->setWinner('winner in progress')
            ->setRating(2.3)
            ->setPlayedAt($playedAt)
            ->setSport($sport);
        $em->persist($sbInProgress);
        $em->flush($sbInProgress);

        $sfInProgress->addSportBet($sbInProgress);
        $this->sportForecasts['inProgress'] = $sfInProgress;

        // To Validate Sport Forecast
        $publishedAt = new \DateTime();
        $interval = new \DateInterval('P2D');
        $publishedAt->sub($interval);
        $sfToValidate = new SportForecast($this->tipster);
        $sfToValidate->setTitle('sport forecast to validate')
            ->setBetting(20)
            ->setIsVip(true)
            ->setPublishedAt($publishedAt);
        $em->persist($sfToValidate);
        $em->flush($sfToValidate);

        $playedAt = new \DateTime();
        $interval = new \DateInterval('P1D');
        $playedAt->sub($interval);
        $sbToValidate = new SportBet($sfToValidate);
        $sbToValidate->setWinner('winner to validate')
            ->setRating(1.9)
            ->setPlayedAt($playedAt)
            ->setSport($sport);
        $em->persist($sbToValidate);
        $em->flush($sbToValidate);

        $sfToValidate->addSportBet($sbToValidate);
        $this->sportForecasts['toValidate'] = $sfToValidate;

    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    public function testEditUnpublished()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecasts['unpublished']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditInProgress()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecasts['inProgress']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditToValidate()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecasts['toValidate']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteUnpublished()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_delete', ['id' => $this->sportForecasts['unpublished']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteInProgress()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_delete', ['id' => $this->sportForecasts['inProgress']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDeleteToValidate()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_delete', ['id' => $this->sportForecasts['toValidate']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPublishUnpublished()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_publish', ['id' => $this->sportForecasts['unpublished']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPublishInProgress()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_publish', ['id' => $this->sportForecasts['inProgress']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPublishToValidate()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_publish', ['id' => $this->sportForecasts['toValidate']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testUnpublishedAddSportBet()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_bet_add', ['id' => $this->sportForecasts['unpublished']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testInProgressAddSportBet()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_bet_add', ['id' => $this->sportForecasts['inProgress']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testToValidateAddSportBet()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_bet_add', ['id' => $this->sportForecasts['toValidate']->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testUnpublishedEditSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['unpublished']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testInProgressEditSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['inProgress']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testToValidateEditSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['toValidate']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testUnpublishedDeleteSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['unpublished']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testInProgressDeleteSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['inProgress']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testToValidateDeleteSportBet()
    {
        $client = $this->login();
        $sportBetId = $this->sportForecasts['toValidate']->getSportBets()[0]->getId();
        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $sportBetId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testUnpublishedValidateSportBet()
    {
        $client = $this->login();
        $sportForecastId = $this->sportForecasts['unpublished']->getId();
        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $sportForecastId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testInProgressValidateSportBet()
    {
        $client = $this->login();
        $sportForecastId = $this->sportForecasts['inProgress']->getId();
        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $sportForecastId]);

        $client->request('GET', $url);

        $this->assertEquals(
            401,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testToValidateValidateSportBet()
    {
        $client = $this->login();
        $sportForecastId = $this->sportForecasts['toValidate']->getId();
        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $sportForecastId]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }
}