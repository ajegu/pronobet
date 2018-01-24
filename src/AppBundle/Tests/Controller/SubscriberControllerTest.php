<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Subscriber;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubscriberControllerTest extends WebTestCase
{
    private $em;
    private $router;
    private $translator;
    private $encoder;

    private $subscriber;

    public function setUp()
    {
        $kernelClass = $this->getKernelClass();
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->router = $kernel->getContainer()->get('router');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $this->subscriber = new Subscriber();
        $this->subscriber
            ->setEmail("test-subscribe@local.host")
            ->setPartners(false)
        ;
    }

    public function tearDown()
    {
        parent::tearDown();

        $subscriber = $this->em->getRepository('AppBundle:Subscriber')
            ->findOneByEmail($this->subscriber->getEmail());

        $this->em->remove($subscriber);
        $this->em->flush();
    }


    public function testSubscribe()
    {
        $client = static::createClient();
        $url = $this->router->generate('homepage');

        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton($this->translator->trans('button.subscribe'))->form();
        $form['email'] = $this->subscriber->getEmail();

        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('button.subscribe'))->form();
        $form['appbundle_subscriber[email]'] = $this->subscriber->getEmail();
        $form['appbundle_subscriber[confirm]'] = 1;

        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains(
            $this->translator->trans('text.newsletter_subscribe_success'),
            $client->getResponse()->getContent()
        );

    }

    public function testConfirmEmail()
    {
        $this->em->persist($this->subscriber);
        $this->em->flush();

        $client = static::createClient();
        $url = $this->router->generate('subscriber_confirm_email', ['id' => $this->subscriber->getId(), 'email' => $this->subscriber->getEmail()]);

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains(
            $this->translator->trans('text.newsletter_confirm_success'),
            $client->getResponse()->getContent()
        );
    }

    public function testUnsubscribe()
    {
        $this->em->persist($this->subscriber);
        $this->em->flush();

        $client = static::createClient();
        $url = $this->router->generate('subscriber_unsubscribe', ['email' => $this->subscriber->getEmail()]);

        $client->request('GET', $url);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains(
            $this->translator->trans('text.newsletter_unscubscribe'),
            $client->getResponse()->getContent()
        );
    }

}
