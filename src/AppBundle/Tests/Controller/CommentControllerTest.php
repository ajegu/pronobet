<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    // entities
    private $userTipster;
    private $tipster;
    private $bookmaker;
    private $sportForecast;
    private $sport;
    private $sportBet;
    private $userFree;
    private $commentText = 'test comment';

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

        // create a tipster
        $this->userTipster = new User();
        $password = $this->encoder->encodePassword($this->userTipster, $plainPassword);
        $this->userTipster->setEmail('tipster@' . $domain)
            ->setNickname('tipster')
            ->setRole('ROLE_TIPSTER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($this->userTipster);
        $this->em->flush($this->userTipster);

        $this->tipster = new Tipster($this->userTipster);
        $this->tipster->setFee(25)
            ->setCommission(20);
        $this->em->persist($this->tipster);
        $this->em->flush($this->tipster);

        // create a sport
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
        $this->em->flush($this->bookmaker);

        // create a published free in progress sport forecast
        $this->sportForecast = new SportForecast($this->tipster);
        $publishedAt = new \DateTime();
        $publishedAt->sub(new \DateInterval('P1D'));
        $this->sportForecast->setIsVip(false)
            ->setBetting(30)
            ->setTitle('sport forecast title')
            ->setBookmaker($this->bookmaker)
            ->setPublishedAt($publishedAt);
        $this->em->persist($this->sportForecast);
        $this->em->flush($this->sportForecast);

        // create a sport bet
        $this->sportBet = new SportBet($this->sportForecast);
        $playedAt = new \DateTime();
        $playedAt->add(new \DateInterval("PT1H"));
        $this->sportBet->setRating(2.1)
            ->setWinner('sport bet winner')
            ->setSport($this->sport)
            ->setAnalysis('sport bet analysis')
            ->setPlayedAt($playedAt);
        $this->em->persist($this->sportBet);
        $this->em->flush($this->sportBet);

        // create a free user
        $this->userFree = new User();
        $password = $this->encoder->encodePassword($this->userFree, $plainPassword);
        $this->userFree->setEmail('free-user@' . $domain)
            ->setNickname('free user')
            ->setRole('ROLE_MEMBER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($this->userFree);
        $this->em->flush($this->userFree);
    }

    public function tearDown()
    {
        parent::tearDown();

        $comment = $this->em->getRepository('AppBundle:Comment')
            ->findOneByText($this->commentText);
        if ($comment) {
            $this->em->remove($comment);
        }

        $this->em->remove($this->userFree);
        $this->em->remove($this->sportBet);
        $this->em->remove($this->sport);
        $this->em->remove($this->sportForecast);
        $this->em->remove($this->bookmaker);
        $this->em->remove($this->tipster);
        $this->em->remove($this->userTipster);

        $this->em->flush();
    }

    public function testAdd()
    {
        $client = static::createClient();

        $url = $this->router->generate('sport_forecast_show', ['id' => $this->sportForecast->getId()]);

        $crawler = $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $form  = $crawler->selectButton($this->translator->trans('button.add_reply'))->form();

        $form['appbundle_comment[text]'] = $this->commentText;

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $crawler = $client->followRedirect();

        $form = $crawler->selectButton($this->translator->trans('button.login'))->form();
        $form['_username'] = $this->userFree->getEmail();
        $form['_password'] = $this->userFree->getPlainPassword();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $client->followRedirect();
        $client->followRedirect();

        $this->assertContains(
            $this->commentText,
            $client->getResponse()->getContent()
        );

    }


}
