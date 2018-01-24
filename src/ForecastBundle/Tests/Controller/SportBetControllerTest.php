<?php

namespace ForecastBundle\Tests\Controller;

use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SportBetControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $user;
    private $tipster;
    private $sportForecast;
    private $sport;
    private $sportBet;

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


        $this->user = new User();
        $password = $this->encoder->encodePassword($this->user, $plainPassword);
        $this->user
            ->setEmail('tipster@' . $domain)
            ->setNickname('tipster')
            ->setRole('ROLE_TIPSTER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->tipster = new Tipster($this->user);
        $this->tipster->setFee(25)
            ->setCommission(20);

        $this->em->persist($this->tipster);
        $this->em->flush();

        $this->sportForecast = new SportForecast($this->tipster);
        $this->sportForecast->setTitle('test sport forecast')
            ->setBetting(20)
            ->setIsVip(false);
        $this->em->persist($this->sportForecast);
        $this->em->flush();

        $this->sport = new Sport();
        $this->sport
            ->setVisible(true)
            ->setName('sport test');
        $this->em->persist($this->sport);
        $this->em->flush($this->sport);

        $this->sportBet = new SportBet($this->sportForecast);
        $this->sportBet->setWinner('winner sport bet')
            ->setPlayedAt(new \DateTime())
            ->setSport($this->sport)
            ->setRating(1.89);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $sportBet = $this->em->getRepository('AppBundle:SportBet')
            ->findOneByWinner($this->sportBet->getWinner());
        if ($sportBet !== null) {
            $this->em->remove($sportBet);
        }

        $this->em->remove($this->sport);
        $this->em->remove($this->sportForecast);
        $this->em->remove($this->tipster);
        $this->em->remove($this->user);

        $this->em->flush();

    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    private function createSportBet()
    {
        $this->em->persist($this->sportBet);
        $this->em->flush($this->sportBet);
    }

    public function testIndex()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('title.sport_forecast_show'),
            $client->getResponse()->getContent()
        );
    }

    public function testAdd()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();
        $form['forecastbundle_sportbet[winner]'] = $this->sportBet->getWinner();
        $form['forecastbundle_sportbet[rating]'] = $this->sportBet->getRating();
        $form['forecastbundle_sportbet[playedAt]'] = $this->sportBet->getPlayedAt()->format('d/m/Y H:i');

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testShow()
    {
        $this->createSportBet();

        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertContains(
            $this->sportBet->getWinner(),
            $client->getResponse()->getContent()
        );
    }

    public function testEdit()
    {
        $this->createSportBet();

        $client = $this->login();
        $url = $this->router->generate('forecast_sport_bet_edit', ['id' => $this->sportBet->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $this->sportBet->setWinner('updated winner');

        $form['forecastbundle_sportbet[winner]'] = $this->sportBet->getWinner();
        $form['forecastbundle_sportbet[rating]'] = $this->sportBet->getRating();
        $form['forecastbundle_sportbet[playedAt]'] = $this->sportBet->getPlayedAt()->format('d/m/Y H:i');

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testDelete()
    {
        $this->createSportBet();

        $client = $this->login();
        $url = $this->router->generate('forecast_sport_bet_delete', ['id' => $this->sportBet->getId()]);

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
    }

}
