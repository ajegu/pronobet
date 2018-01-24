<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubscriptionControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $tipster;
    private $user;

    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $this->user = new User();

        $plainPassword = 'tamtam';
        $password = $this->encoder->encodePassword($this->user, $plainPassword);
        $this->user->setEmail('user@prono-bet.com')
            ->setNickname('user')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($this->user);
        $this->em->flush();

        $userTipster = new User();
        $userTipster->setEmail('tipster@prono-bet.com')
            ->setNickname('tipster')
            ->setPlainPassword($plainPassword)
            ->setPassword($password);
        $this->em->persist($userTipster);
        $this->em->flush();

        $this->tipster = new Tipster($userTipster);
        $this->tipster->setCommission(20)
            ->setFee(25);
        $this->em->persist($this->tipster);
        $this->em->flush();

        $this->tipster->setUser($userTipster);
    }

    public function tearDown()
    {
        $subscriptions = $this->em->getRepository('AppBundle:Subscription')
            ->findAll();
        foreach ($subscriptions as $subscription) {
            $this->em->remove($subscription);
            $this->em->flush();
        }

        $this->em->remove($this->user);
        $userTipster = $this->tipster->getUser();
        $this->em->remove($this->tipster);
        $this->em->remove($userTipster);

        $this->em->flush();
    }

    public function login()
    {
        $url = $this->router->generate('login');
        $client = SecurityControllerTest::login($this->user, $url, $this->translator);
        return $client;
    }

    public function createSubscription()
    {
        $createdAt = new \DateTime();
        $finishedAt = new \DateTime();
        $finishedAt->add(new \DateInterval("P1M"));

        $subscription = new Subscription($this->tipster, $this->user);
        $subscription->setCreatedAt($createdAt)
            ->setAmount($this->tipster->getFee())
            ->setFees($this->tipster->getFee() * $this->tipster->getCommission() / 100)
            ->setEmailNotification(true)
            ->setSmsNotification(true)
            ->setStatus(SubscriptionStatus::Vip)
            ->setActivate(true)
            ->setFinishedAt($finishedAt);

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription->getId();
    }

    public function testSubscribe()
    {
        $url = $this->router->generate('subscription_subscribe', ['id' => $this->tipster->getId()]);
        $client = static::createClient();

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testSubscribeWithActiveSubscription()
    {

        $this->createSubscription();

        $url = $this->router->generate('subscription_subscribe', ['id' => $this->tipster->getId()]);
        $client = $this->login();

        $client->request('GET', $url);

        $this->assertContains(
            $this->translator->trans('title.existing_subscription'),
            $client->getResponse()->getContent()
        );
    }

    public function testSubscribeForFree()
    {
        $url = $this->router->generate('subscription_subscribe_for_free', ['id' => $this->tipster->getId()]);
        $client = static::createClient();

        $client->request('GET', $url);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $client = $this->login();
        $client->request('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // check user account
        $url = $this->router->generate('user_tipster_subscription');
        $client->request('GET', $url);

        $this->assertContains(
            $this->tipster->getUser()->getNickname(),
            $client->getResponse()->getContent()
        );
    }

    public function testEditNotifications()
    {
        $subscriptionId = $this->createSubscription();
        $url = $this->router->generate('user_tipster_subscription');

        $client = $this->login();
        $crawler = $client->request('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink($this->translator->trans('label.edit_notifications'))->link();

        $crawler = $client->click($link);
        $form = $crawler->selectButton($this->translator->trans('button.edit_notification'))->form();
        $form['appbundle_subscription[smsNotification]']  =false;

        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $subscription = $this->em->getRepository('AppBundle:Subscription')
            ->findOneBy(['smsNotification' => false]);


        $this->assertEquals($subscription->getId(), $subscriptionId);
    }

    public function testUnsubscribe()
    {
        $url = $this->router->generate('subscription_subscribe_for_free', ['id' => $this->tipster->getId()]);
        $client = $this->login();
        $client->request('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $url = $this->router->generate('subscription_unsubscribe', ['id' => $this->tipster->getId()]);
        $client->request('GET', $url);

        $subscription = $this->em->getRepository('AppBundle:Subscription')
            ->findOneBy(['user' => $this->user]);

        $this->assertNull($subscription);
    }
}
