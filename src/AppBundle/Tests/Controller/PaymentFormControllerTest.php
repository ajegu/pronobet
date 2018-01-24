<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 12/08/17
 * Time: 10:14
 */

namespace AppBundle\Tests\Controller;


class PaymentFormControllerTest extends \PHPUnit_Extensions_Selenium2TestCase
{
    private $process;

    public function setUp()
    {
        $this->setBrowser('google-chrome');
        $this->setBrowserUrl('http://127.0.0.1:8000/');


        $command = 'php ' . __DIR__ . '/../../../../bin/console fake:populate';
        exec($command);

    }

    public function tearDown()
    {
        $command = __DIR__ . '/../../../../bin/reset_database.sh';
        exec($command);
    }

    public function testTitle()
    {


        $this->url('http://127.0.0.1:8000/tipster');

        $this->byCssSelector('.hide-button')->click();

        $this->byCssSelector('.vip-button')->click();
        $this->byCssSelector('body > div.pusher > div.site-content > div:nth-child(2) > div > div > a')->click();


        $form = $this->byCssSelector('form');
        $username = $this->byName('_username');
        $password = $this->byName('_password');

        $username->value('user-vip@prono-bet.com');
        $password->value('user-vip@prono-bet.com');

        $form->submit();

        $this->assertEquals(
            "Informations de facturation",
            $this->byCssSelector('h1')->text()
        );

        $form = $this->byCssSelector('form');

        $firstname = $this->byName('appbundle_user[firstName]');
        $firstname->value('user');

        $lastname = $this->byName('appbundle_user[lastName]');
        $lastname->value('vip');

        $birthday = $this->byName('appbundle_user[birthday]');
        $birthday->value('06/09/1984');

        $form->submit();

        $this->assertEquals(
            "Finaliser votre paiement",
            $this->byCssSelector('h1')->text()
        );

        $form = $this->byCssSelector('form');

        $cardNumber = $this->byName('cardNumber');
        $cardNumber->value('4706750000000033');

        $month = $this->byCssSelector('#month-container');
        $month->click();
        sleep(1);
        $monthValue = $this->byCssSelector('[data-value="02"]');
        $monthValue->click();
        sleep(1);
        $year = $this->byCssSelector('#year-container');
        $year->click();
        sleep(1);
        $yearvalue = $this->byCssSelector('[data-value="19"]');
        $yearvalue->click();
        sleep(1);

        $cardCvx = $this->byName('cardCvx');
        $cardCvx->value('123');

        $form->submit();

        $this->assertEquals(
            "Validation de votre paiment",
            $this->byCssSelector('h1')->text()
        );
    }
}