<?php

namespace ForecastBundle\Tests\Controller;

use AppBundle\Entity\Country;
use AppBundle\Entity\Nationality;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Tests\Controller\SecurityControllerTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class SubscriptionControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;
    private $mangopay;

    private $tipster;
    private $nationality;
    private $country;
    private $user;
    private $subscription;


    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');
        $this->mangopay = $kernel->getContainer()->get('mangopay');

        $userTipster = new User();
        $plainPassword = 'tamtam';
        $password = $this->encoder->encodePassword($userTipster, $plainPassword);
        $userTipster->setEmail('tipster@prono-bet.com')
            ->setNickname('tipster')
            ->setPlainPassword($plainPassword)
            ->setRole('ROLE_TIPSTER')
            ->setPassword($password);
        $this->em->persist($userTipster);
        $this->em->flush();

        $this->tipster = new Tipster($userTipster);
        $this->tipster->setCommission(20)
            ->setFee(25);
        $this->em->persist($this->tipster);
        $this->em->flush();

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
        if ($this->subscription !== null) {
            $this->em->remove($this->subscription);
        }

        if ($this->user !== null) {
            $this->em->remove($this->user);
        }

        $bankWires = $this->em->getRepository('AppBundle:BankWire')
            ->findAll();

        foreach ($bankWires as $bankWire) {
            $this->em->remove($bankWire);
            $this->em->flush($bankWire);
        }

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
        $client = SecurityControllerTest::login($this->tipster->getUser(), $url, $this->translator);
        return $client;
    }

    public function testAccountsetting()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_subscription_account_setting');
        $client->request('GET', $url);

        $this->assertContains(
            $this->translator->trans('label.activate_payment_account'),
            $client->getResponse()->getContent()
        );
    }

    public function testFullProcess()
    {
        $client = $this->login();
        $url = $this->router->generate('forecast_subscription_account_setting');
        $crawler = $client->request('GET', $url);

        // Create payment account
        $link = $crawler->selectLink($this->translator->trans('label.activate_payment_account'))->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();

        $form['forecastbundle_user[firstName]'] = 'user';
        $form['forecastbundle_user[lastName]'] = 'tipster';
        $form['forecastbundle_user[birthday]'] = '1984-09-06';
        $form['forecastbundle_user[nationality]'] = $this->nationality->getId();
        $form['forecastbundle_user[country]'] = $this->country->getId();
        $form['forecastbundle_user[addressLine1]'] = '14 rue des Lilas';
        $form['forecastbundle_user[postalCode]'] = '35000';
        $form['forecastbundle_user[city]'] = 'Rennes';
        $form['forecastbundle_user[occupation]'] = 'Testing';
        $form['forecastbundle_user[incomeRange]'] = '1';

        $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $this->em->refresh($this->tipster);
        $this->assertNotNull($this->tipster->getMangoPayId());

        // Edit payment account
        $url = $this->router->generate('forecast_subscription_edit_payment_account');
        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();
        $form['forecastbundle_user[lastName]'] = 'tipster updated';

        $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        // bank Account Setting
        $url = $this->router->generate('forecast_bank_account_setting');
        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();
        $form['appbundle_bankaccount[iban]'] = 'FR3020041010124530725S03383';
        $form['appbundle_bankaccount[bic]'] = 'CRLYFRPP';
        $form['appbundle_bankaccount[ownerName]'] = 'User Tipster';

        $form['appbundle_bankaccount[country]'] = $this->country->getId();
        $form['appbundle_bankaccount[addressLine1]'] = '14 rue des Lilas';
        $form['appbundle_bankaccount[postalCode]'] = '35000';
        $form['appbundle_bankaccount[city]'] = 'Rennes';

        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->em->refresh($this->tipster);
        $this->assertNotNull($this->tipster->getMangoPayBankAccountId());

        // bank account edit
        $url = $this->router->generate('forecast_edit_bank_account');
        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton($this->translator->trans('button.save'))->form();
        $form['appbundle_bankaccount[ownerName]'] = 'User Tipster Updated';
        $form['appbundle_bankaccount[iban]'] = 'FR3020041010124530725S03383';
        $form['appbundle_bankaccount[bic]'] = 'CRLYFRPP';

        $form['appbundle_bankaccount[country]'] = $this->country->getId();
        $form['appbundle_bankaccount[addressLine1]'] = '14 rue des Lilas';
        $form['appbundle_bankaccount[postalCode]'] = '35000';
        $form['appbundle_bankaccount[city]'] = 'Rennes';

        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'User Tipster Updated',
            $client->getResponse()->getContent()
        );

        // Wallet transfer
        $result = $this->getSubscription();
        $this->assertTrue($result);

        exec('php ' . __DIR__ . '/../../../../bin/console wallet:transfer');

        $url = $this->router->generate('forecast_payout_setting');
        $crawler = $client->request('GET', $url);
        $this->assertContains(
            $this->translator->trans('label.create_payout'),
            $client->getResponse()->getContent()
        );

        $link = $crawler->selectLink($this->translator->trans('label.create_payout'))->link();
        $client->click($link);

        $this->assertContains(
            $this->translator->trans('text.payout_succeeded'),
            $client->getResponse()->getContent()
        );

    }


    private function getSubscription()
    {
        // get the member mango pay id
        $members = [];
        $page = 1;
        while(count($members) > 0 || $page === 1) {
            $members = $this->mangopay->listUsers($page);
            foreach ($members as $member) {
                if ($member->Email === 'user-vip@prono-bet.com') {
                    // check wallet
                    $wallets = $this->mangopay->listWallets($member->Id);
                    foreach ($wallets as $wallet) {
                        if ($wallet->Balance->Amount > 10) {
                            $this->createUserSubscription($member->Id, $wallet);
                            return true;
                        }
                    }
                }
            }
            $page++;
        }

        return false;
    }

    private function createUserSubscription($mangoPayId, $wallet)
    {
        $this->user = new User();
        $plainPassword = 'tamtam';
        $password = $this->encoder->encodePassword($this->user, $plainPassword);
        $this->user->setEmail('user-vip@prono-bet.com')
            ->setNickname('user-vip')
            ->setPlainPassword($plainPassword)
            ->setRole('ROLE_MEMBER')
            ->setMangoPayId($mangoPayId)
            ->setMangoPayWalletId($wallet->Id)
            ->setPassword($password);
        $this->em->persist($this->user);
        $this->em->flush();

        $amount = $wallet->Balance->Amount / 100;
        $fees = round($amount * $this->tipster->getCommission() / 100, 2);
        $amount = $amount - $fees;

        $createdAt = new \DateTime();
        $createdAt->sub(new \DateInterval("P2M"));
        $finishedAt = new \DateTime();
        $finishedAt->sub(new \DateInterval("P1M"));

        $this->subscription = new Subscription($this->tipster, $this->user);
        $this->subscription->setActivate(true)
            ->setCreatedAt($createdAt)
            ->setAmount($amount)
            ->setFees($fees)
            ->setSmsNotification(true)
            ->setEmailNotification(true)
            ->setStatus(SubscriptionStatus::Vip)
            ->setFinishedAt($finishedAt);

        $this->em->persist($this->subscription);
        $this->em->flush();
    }

}
