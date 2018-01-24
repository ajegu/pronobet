<?php

namespace ForecastBundle\Tests\Controller;

use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        $userTipster = new User();
        $plainPassword = 'tamtam';
        $password = $this->encoder->encodePassword($userTipster, $plainPassword);
        $userTipster->setEmail('tipster@' . $domain)
            ->setNickname('tipster')
            ->setRole('ROLE_TIPSTER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($userTipster);
        $this->em->flush();

        $this->tipster = new Tipster($userTipster);
        $this->tipster->setFee(25)
            ->setCommission(20);

        $this->em->persist($this->tipster);
        $this->em->flush();

        $this->sportForecast = new SportForecast($this->tipster);
        $this->sportForecast->setTitle('test sport forecast')
            ->setBetting(20)
            ->setIsVip(false);

        $this->sport = new Sport();
        $this->sport->setName('sport test')
            ->setVisible(true);
        $this->em->persist($this->sport);
        $this->em->flush($this->sport);

        $this->bookmaker = new Bookmaker();
        $this->bookmaker->setName('bookmaker')
            ->setBonus(0)
            ->setWebsiteLink('http://bookmaker.test/')
            ->setVisible(true);
        $this->em->persist($this->bookmaker);
        $this->em->flush();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $sportForecast = $this->em->getRepository('AppBundle:SportForecast')
            ->findOneByTitle($this->sportForecast->getTitle());

        if ($sportForecast !== null) {
            $this->em->remove($sportForecast);
            $this->em->flush();
        }

        $this->em->remove($this->sport);
        $userTipster = $this->tipster->getUser();
        $this->em->remove($this->tipster);
        $this->em->remove($userTipster);
        $this->em->remove($this->bookmaker);

        $this->em->flush();

    }

    private function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    private function createSportForecast()
    {
        $this->em->persist($this->sportForecast);
        $this->em->flush($this->sportForecast);
    }

    private function createSportBet()
    {
        $sportBet = new SportBet($this->sportForecast);
        $playedAt = new \DateTime();
        $playedAt->add(new \DateInterval('P1D'));
        $sportBet->setWinner('test winner')
            ->setRating(1.2)
            ->setPlayedAt($playedAt)
            ->setSport($this->sport);

        $this->em->persist($sportBet);
        $this->em->flush($sportBet);

        $this->sportForecast->addSportBet($sportBet);
    }

    public function testUnpublished()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_unpublished');

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->translator->trans('title.sport_forecast_managment'),
            $client->getResponse()->getContent()
        );
    }

    public function testShow()
    {
        $this->createSportForecast();
        $url = $this->router->generate('forecast_sport_forecast_show', ['id' => $this->sportForecast->getId()]);
        $client = $this->login();

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->sportForecast->getTitle(),
            $client->getResponse()->getContent()
        );

    }

    public function testAdd()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_add');

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.add'))->form();

        $form['sportforecastbundle_sportforecast[title]'] = $this->sportForecast->getTitle();
        $form['sportforecastbundle_sportforecast[betting]'] = $this->sportForecast->getBetting();
        $form['sportforecastbundle_sportforecast[isVip]'] = (int)$this->sportForecast->getIsVip();
        $form['sportforecastbundle_sportforecast[bookmaker]'] = $this->bookmaker->getId();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEdit()
    {
        $this->createSportForecast();
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_edit', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

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

    public function testDelete()
    {
        $this->createSportForecast();
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_delete', ['id' => $this->sportForecast->getId()]);

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

    public function testPublish()
    {
        $this->createSportForecast();
        $this->createSportBet();
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_publish', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.publish'))->form();
        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testSubmitChange()
    {
        $this->createSportForecast();
        $this->createSportBet();
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_submit_change', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.submit_change'))->form();
        $form['forecastbundle_sportforecast[message]'] = 'test message';

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testEditTicket()
    {
        $this->createSportForecast();

        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_edit_ticket', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.edit_ticket'))->form();

        $ticket = new UploadedFile(
            __DIR__ . '/../Images/simple_01.jpg',
            'simple_01',
            'image/jpeg',
            1949
        );
        $form['sportforecastbundle_sportforecast[ticketFile]'] = $ticket;

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testValidate()
    {
        $publishedAt = $playedAt = new \DateTime();
        $publishedAt->sub(new \DateInterval('P2D'));
        $sportForecast = new SportForecast($this->tipster);
        $sportForecast->setTitle('sport forecast to validate')
            ->setPublishedAt($publishedAt)
            ->setIsVip(true)
            ->setBetting(0);
        $this->em->persist($sportForecast);
        $this->em->flush($sportForecast);

        $playedAt->sub(new \DateInterval('P1D'));
        $sportBet = new SportBet($sportForecast);
        $sportBet->setWinner('winner')
            ->setRating(1)
            ->setPlayedAt($playedAt)
            ->setSport($this->sport);

        $this->em->persist($sportBet);
        $this->em->flush($sportBet);

        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_validate', ['id' => $sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.validate'))->form();

        $form['result_' . $sportBet->getId()] = 1;

        $crawler = $client->submit($form);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.validate_confirm'))->form();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $this->em->remove($sportBet);
        $this->em->remove($sportForecast);
        $this->em->flush();
    }

    public function testCancellation()
    {
        $this->createSportForecast();
        $this->createSportBet();
        $client = $this->login();
        $url = $this->router->generate('forecast_sport_forecast_cancellation', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form = $crawler->selectButton($this->translator->trans('button.cancellation_confirm'))->form();
        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

    }

}
