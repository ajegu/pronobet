<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 31/07/17
 * Time: 20:16
 */

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Country;
use AppBundle\Entity\Nationality;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class PaymentControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $tipster;
    private $user;
    private $nationality;
    private $country;

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

        $this->nationality = new Nationality();
        $this->nationality->setName('France')
            ->setAlpha2('FR')
            ->setAlpha3('FRA');
        $this->em->persist($this->nationality);
        $this->em->flush();

        $this->country = new Country();
        $this->country->setName('France')
            ->setAlpha2('FR')
            ->setAlpha3('FRA');
        $this->em->persist($this->country);
        $this->em->flush();
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

        $this->em->remove($this->nationality);
        $this->em->remove($this->country);

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

    public function testCheckout()
    {
        $client = $this->login();
        $url = $this->router->generate('payment_checkout', ['id' => $this->tipster->getId()]);
        $client->request('GET', $url);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $crawler = $client->followRedirect();

        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();
        $form['appbundle_user[firstName]'] = 'firstName';
        $form['appbundle_user[lastName]'] = 'lastName';
        $form['appbundle_user[birthday]'] = '1984-09-06';
        $form['appbundle_user[nationality]'] = $this->nationality->getId();
        $form['appbundle_user[country]'] = $this->country->getId();

        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }

    public function testSuccess()
    {
        $client = static::createClient();
        $url = $this->router->generate('payment_success');

        $client->request('GET', $url);

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testFailure()
    {
        $client = $this->login();
        $url = $this->router->generate('payment_failure');

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

    }

}