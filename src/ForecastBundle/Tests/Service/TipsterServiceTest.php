<?php


namespace ForecastBundle\Tests\Service;



use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use ForecastBundle\Service\TipsterService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TipsterServiceTest extends KernelTestCase
{
    private $em;
    private $ra;
    private $encoder;

    // entities
    private $userTipster;
    private $tipster;
    private $sport;
    private $sportForecastFree;
    private $sportBetFree;
    private $sportForecastVip;
    private $sportBetVip;

    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');
        $this->ra = $kernel->getContainer()->get('app.cache.tipster');

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

        // create a free validated loose sport forecast
        $this->sportForecastFree = new SportForecast($this->tipster);
        $publishedAt = new \DateTime();
        $publishedAt->sub(new \DateInterval('P1D'));
        $this->sportForecastFree->setIsVip(false)
            ->setBetting(30)
            ->setIsValidate(true)
            ->setTitle('sport forecast title free')
            ->setPublishedAt($publishedAt);
        $this->em->persist($this->sportForecastFree);
        $this->em->flush($this->sportForecastFree);

        // create a sport bet
        $this->sportBetFree = new SportBet($this->sportForecastFree);
        $playedAt = new \DateTime();
        $playedAt->sub(new \DateInterval("PT1H"));
        $this->sportBetFree->setRating(2.1)
            ->setWinner('sport bet free winner')
            ->setSport($this->sport)
            ->setAnalysis('sport bet free analysis')
            ->setIsWon(false)
            ->setPlayedAt($playedAt);
        $this->em->persist($this->sportBetFree);
        $this->em->flush($this->sportBetFree);

        $this->sportForecastFree->addSportBet($this->sportBetFree);

        // create a vip validated win sport forecast
        $this->sportForecastVip = new SportForecast($this->tipster);
        $publishedAt = new \DateTime();
        $publishedAt->sub(new \DateInterval('P1D'));
        $this->sportForecastVip->setIsVip(true)
            ->setBetting(75)
            ->setIsValidate(true)
            ->setTitle('sport forecast title vip')
            ->setPublishedAt($publishedAt);
        $this->em->persist($this->sportForecastVip);
        $this->em->flush($this->sportForecastVip);

        // create a sport bet
        $this->sportBetVip = new SportBet($this->sportForecastVip);
        $playedAt = new \DateTime();
        $playedAt->sub(new \DateInterval("PT1H"));
        $this->sportBetVip->setRating(1.9)
            ->setWinner('sport bet vip winner')
            ->setSport($this->sport)
            ->setAnalysis('sport bet vip analysis')
            ->setIsWon(true)
            ->setPlayedAt($playedAt);
        $this->em->persist($this->sportBetVip);
        $this->em->flush($this->sportBetVip);

        $this->sportForecastVip->addSportBet($this->sportBetVip);

    }

    public function tearDown()
    {
        $this->em->remove($this->sportBetFree);
        $this->em->remove($this->sportBetVip);
        $this->em->remove($this->sport);
        $this->em->remove($this->sportForecastFree);
        $this->em->remove($this->sportForecastVip);
        $this->em->remove($this->tipster);
        $this->em->remove($this->userTipster);

        $this->em->flush();
    }

    public function testCalculateStats()
    {
        $tipsterService = new TipsterService(
            $this->em,
            $this->ra,
            $this->tipster
        );

        $stats = $tipsterService->calculateStats();

        $this->assertEquals(
            $stats['sportForecastStats']['all']['winrate'],
            50
        );

        $this->assertEquals(
            $stats['sportForecastStats']['all']['roi'],
            1.36
        );

        $this->assertEquals(
            $stats['sportForecastStats']['all']['played'],
            2
        );

        $this->assertEquals(
            $stats['sportForecastStats']['all']['won'],
            1
        );

        $this->assertEquals(
            $stats['sportForecastStats']['all']['winnings'],
            142.50
        );

        $this->assertEquals(
            $stats['sportForecastStats']['all']['bettings'],
            105
        );

        $this->assertEquals(
            $stats['sportDataSet']['all']['data'][0],
            50
        );

    }

}