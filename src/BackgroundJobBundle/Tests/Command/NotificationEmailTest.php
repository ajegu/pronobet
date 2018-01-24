<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 15/08/17
 * Time: 21:50
 */

namespace BackgroundJobBundle\Tests\Command;


use AppBundle\Entity\Bookmaker;
use AppBundle\Entity\Sport;
use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use BackgroundJobBundle\Command\NotificationEmailCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NotificationEmailTest extends KernelTestCase
{
    private $application;
    private $em;

    private $user;
    private $tipster;
    private $subscription;
    private $sport;
    private $bookmaker;
    private $sportForecast;
    private $sportBet;

    public function setUp()
    {
        self::bootKernel();
        $this->application = new Application(self::$kernel);
        $this->application->add(new NotificationEmailCommand());
        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $encoder = self::$kernel->getContainer()->get('security.password_encoder');
        $aws = self::$kernel->getContainer()->get('aws');

        $this->user = new User();
        $plainPassword = 'tamtam';
        $password = $encoder->encodePassword($this->user, $plainPassword);
        $this->user->setEmail('user-free@prono-bet.com')
            ->setNickname('user-free')
            ->setRole('ROLE_MEMBER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($this->user);

        $userTipster = new User();
        $userTipster->setEmail('tipster@prono-bet.com')
            ->setNickname('tipster')
            ->setRole('ROLE_TIPSTER')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($userTipster);

        $this->tipster = new Tipster($userTipster);
        $this->tipster->setFee(25)
            ->setCommission(20);
        $this->em->persist($this->tipster);

        $this->subscription = new Subscription($this->tipster, $this->user);
        $this->subscription->setStatus(SubscriptionStatus::Free)
            ->setActivate(true)
            ->setAmount(0)
            ->setFees(0)
            ->setEmailNotification(true)
            ->setSmsNotification(false);
        $this->em->persist($this->subscription);

        $this->bookmaker = new Bookmaker();
        $this->bookmaker->setName('bookmaker')
            ->setBonus(0)
            ->setWebsiteLink('http://bookmaker.test/')
            ->setVisible(true);
        $this->em->persist($this->bookmaker);


        $this->sportForecast = new SportForecast($this->tipster);
        $this->sportForecast->setBetting(20)
            ->setIsVip(false)
            ->setBookmaker($this->bookmaker);
        $this->em->persist($this->sportForecast);


        $this->sport = new Sport();
        $this->sport->setName('sport test')
            ->setVisible(true);
        $this->em->persist($this->sport);

        $this->sportBet = new SportBet($this->sportForecast);
        $this->sportBet->setWinner('test winner')
            ->setPlayedAt(new \DateTime())
            ->setRating(2.54)
            ->setIsWon(false)
            ->setConfidenceIndex(4)
            ->setSport($this->sport);
        $this->em->persist($this->sportBet);

        $this->em->flush();

        $this->em->refresh($this->sportForecast);

        // send message
        $queueUrl = self::$kernel->getContainer()->getParameter('aws_sqs.notification_email');
        $result = $aws->receiveMessages($queueUrl);
        $message = $result->get('Messages');
        $sportForecastId = $message[0]['Body'];

        if ($sportForecastId !== null) {
            $aws->purgeQueue($queueUrl);
        }

        $aws->sendMessage($this->sportForecast->getId(), $queueUrl);

    }

    public function tearDown()
    {
        $this->em->remove($this->sportBet);
        $this->em->remove($this->sport);
        $this->em->remove($this->sportForecast);
        $this->em->remove($this->bookmaker);
        $this->em->remove($this->subscription);
        $this->em->remove($this->user);
        $userTipster = $this->tipster->getUser();
        $this->em->remove($this->tipster);
        $this->em->remove($userTipster);

        $this->em->flush();
    }

    public function testExecute()
    {
        $command = $this->application->find('notification:email');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--env' => 'test']);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'email sent to ' . $this->user->getEmail(),
            $output
        );
    }

}