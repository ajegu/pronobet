<?php


namespace AppBundle\Tests\Controller;


use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TipsterControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    // entities
    private $userTipster;
    private $tipster;
    private $userFree;

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

        $subscriptions = $this->em->getRepository('AppBundle:Subscription')
            ->findAll();
        foreach ($subscriptions as $subscription) {
            $this->em->remove($subscription);
            $this->em->flush($subscription);
        }

        $this->em->remove($this->userFree);
        $this->em->remove($this->tipster);
        $this->em->remove($this->userTipster);

        $this->em->flush();
    }

    public function testIndex()
    {
        $client = static::createClient();
        $url = $this->router->generate('tipster_index');

        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains(
            $this->userTipster->getNickname(),
            $client->getResponse()->getContent()
        );
    }

    public function testShow()
    {
        $client = static::createClient();
        $url = $this->router->generate('tipster_show', ['id' => $this->tipster->getId()]);
        $client->request('GET', $url);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testSubscribeForFree()
    {
        $client = static::createClient();
        $url = $this->router->generate('tipster_show', ['id' => $this->tipster->getId()]);
        $crawler = $client->request('GET', $url);

        $link = $crawler->selectLink($this->translator->trans('label.join'))->link();

        $crawler = $client->click($link);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('button.login'))->form();
        $form['_username'] = $this->userFree->getEmail();
        $form['_password'] = $this->userFree->getPlainPassword();

        $client->submit($form);

        $this->assertEquals(
            302,
            $client->getResponse()->getStatusCode()
        );

        $client->followRedirect();

        $this->assertContains(
            $this->translator->trans('message.subscribe_free'),
            $client->getResponse()->getContent()
        );
    }
}