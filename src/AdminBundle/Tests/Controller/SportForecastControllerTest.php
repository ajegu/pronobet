<?php

namespace AdminBundle\Tests\Controller;

use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SportForecastControllerTest extends WebTestCase
{

    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $tipster;
    private $sportForecast;
    private $sport;
    private $bookmaker;
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

        $userTipster = new User();
        $plainPassword = 'tamtam';
        $password = $this->encoder->encodePassword($userTipster, $plainPassword);
        $userTipster->setEmail('tipster@prono-bet.com')
            ->setNickname('tipster')
            ->setRole('ROLE_ADMIN')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($userTipster);

        $this->tipster = new Tipster($userTipster);
        $this->tipster->setFee(25)
            ->setCommission(20);

        $this->em->persist($this->tipster);

        $this->sportForecast = new SportForecast($this->tipster);
        $this->sportForecast->setTitle('test sport forecast')
            ->setPublishedAt(new \DateTime())
            ->setIsValidate(true)
            ->setBetting(20)
            ->setIsVip(false);
        $this->em->persist($this->sportForecast);

        $this->sport = new Sport();
        $this->sport->setName('sport test')
            ->setVisible(true);
        $this->em->persist($this->sport);


        $this->bookmaker = new Bookmaker();
        $this->bookmaker->setName('bookmaker')
            ->setBonus(0)
            ->setWebsiteLink('http://bookmaker.test/')
            ->setVisible(true);
        $this->em->persist($this->bookmaker);
        $this->em->flush();

        $this->sportBet = new SportBet($this->sportForecast);
        $this->sportBet->setPlayedAt(new \DateTime())
            ->setWinner('test winner')
            ->setRating(5)
            ->setIsWon(true)
            ->setCancelled(true)
            ->setSport($this->sport);
        $this->em->persist($this->sportBet);

        $this->em->flush();
    }

    public function tearDown()
    {
        $this->em->remove($this->sportBet);
        $this->em->remove($this->sportForecast);
        $userTipster = $this->tipster->getUser();
        $this->em->remove($this->tipster);
        $this->em->remove($userTipster);
        $this->em->remove($this->sport);
        $this->em->remove($this->bookmaker);

        $this->em->flush();
    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    public function testIndex()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_index');

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_show_sport_forecast', ['id' => $this->sportForecast->getId()]);

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testEditSportForecast()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_edit_sport_forecast', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('button.edit'))->form();

        $this->sportForecast->setTitle('updated title sport forecast');

        $form['sportforecastbundle_sportforecast[title]'] = $this->sportForecast->getTitle();
        $form['sportforecastbundle_sportforecast[betting]'] = $this->sportForecast->getBetting();
        $form['sportforecastbundle_sportforecast[isVip]'] = (int)$this->sportForecast->getIsVip();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $client->followRedirect();

        $this->assertContains(
            $this->sportForecast->getTitle(),
            $client->getResponse()->getContent()
        );
    }

    public function testEditSportBet()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_edit_sport_bet', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

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

        $client->followRedirect();

        $this->assertContains(
            $this->sportBet->getWinner(),
            $client->getResponse()->getContent()
        );
    }

    public function testRestoreUnpublished()
    {
        $this->sportForecast->setIsValidate(false);
        $this->em->persist($this->sportForecast);
        $this->em->flush();

        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_restore_unpublished', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('button.restore'))->form();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $this->em->refresh($this->sportForecast);

        $this->assertNull($this->sportForecast->getPublishedAt());
    }

    public function testRestoreToValidate()
    {
        $client = $this->login();
        $url = $this->router->generate('admin_sport_forecast_restore_to_validate', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('button.restore'))->form();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $this->em->refresh($this->sportForecast);
        $this->em->refresh($this->sportBet);

        $this->assertFalse($this->sportForecast->getIsValidate());

        $this->assertFalse($this->sportBet->getIsWon());
        $this->assertFalse($this->sportBet->getCancelled());
    }
}
