<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 07/07/17
 * Time: 22:32
 */

namespace AppBundle\Service;


use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentType;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use MangoPay\Address;
use MangoPay\BankAccount;
use MangoPay\BankAccountDetailsIBAN;
use MangoPay\CardRegistration;
use MangoPay\CardRegistrationStatus;
use MangoPay\KycDocument;
use MangoPay\KycDocumentStatus;
use MangoPay\KycDocumentType;
use MangoPay\Libraries\ResponseException;
use MangoPay\MangoPayApi;
use MangoPay\Money;
use MangoPay\Pagination;
use MangoPay\PayIn;
use MangoPay\PayInExecutionDetailsDirect;
use MangoPay\PayInPaymentDetailsCard;
use MangoPay\PayInStatus;
use MangoPay\PayOut;
use MangoPay\PayOutPaymentDetailsBankWire;
use MangoPay\PayOutPaymentType;
use MangoPay\Transfer;
use MangoPay\UserNatural;
use MangoPay\Wallet;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class MangoPayService
{
    private $clientId;
    private $passPhrase;
    private $rootDir;
    private $em;
    private $session;
    private $router;
    private $logger;
    private $tokenStorage;

    /**
     * MangoPayService constructor.
     * @param string $clientId
     * @param string $passPhrase
     * @param string $rootDir
     * @param EntityManager $em
     * @param Session $session
     * @param Router $router
     * @param Logger $logger
     */
    public function __construct($clientId, $passPhrase, $rootDir, EntityManager $em, Session $session, Router $router, Logger $logger, TokenStorage $tokenStorage)
    {
        $this->clientId = $clientId;
        $this->passPhrase = $passPhrase;
        $this->rootDir = $rootDir;
        $this->em = $em;
        $this->session = $session;
        $this->router = $router;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return MangoPayApi
     */
    private function init()
    {
        $mangoPayApi = new MangoPayApi();
        $mangoPayApi->Config->ClientId = $this->clientId;
        $mangoPayApi->Config->ClientPassword = $this->passPhrase;
        $mangoPayApi->Config->TemporaryFolder = $this->rootDir . '/../var/';

        return $mangoPayApi;
    }

    /**
     * @param $userId
     * @return Wallet[]
     */
    public function listWallets($userId)
    {
        $mangoPayApi = $this->init();
        $wallets = $mangoPayApi->Users->GetWallets($userId);

        return $wallets;
    }

    /**
     * @param $page int
     * @return array
     */
    public function listUsers($page)
    {
        $mangoPayApi = $this->init();
        $pagination = new Pagination($page, 10);
        $users = $mangoPayApi->Users->GetAll($pagination);

        return $users;
    }

    /**
     * @param $kycDocumentId
     * @return KycDocument
     */
    public function viewKYCDocument($kycDocumentId)
    {
        $mangoPayApi = $this->init();
        $kycDocument = $mangoPayApi->KycDocuments->Get($kycDocumentId);

        return $kycDocument;
    }

    /**
     * @param $file
     * @param $type
     * @param $userId
     * @return KycDocument
     */
    public function submitKYCDocument($file, $type, $userId)
    {
        $mangoPayApi = $this->init();

        $kycDocument = new KycDocument();
        $kycDocument->Type = $type;

        $createdKYCDocument = $mangoPayApi->Users->CreateKycDocument($userId, $kycDocument);
        $kycDocumentId = $createdKYCDocument->Id;

        // add page
        $mangoPayApi->Users->CreateKycPageFromFile($userId, $createdKYCDocument->Id, $file);

        // submit the doc
        $kycDocument = new KycDocument();
        $kycDocument->Id = $kycDocumentId;
        $kycDocument->Status = KycDocumentStatus::ValidationAsked;
        $mangoPayApi->Users->UpdateKycDocument($userId, $kycDocument);

        return $kycDocument;
    }

    /**
     * @param $payoutId
     * @return PayOut
     */
    public function viewPayout($payoutId)
    {
        $mangoPayApi = $this->init();
        $result = $mangoPayApi->PayOuts->Get($payoutId);

        return $result;
    }

    /**
     * @param Tipster $tipster
     * @param Wallet $wallet
     * @return PayOut
     */
    public function createPayout(Tipster $tipster, Wallet $wallet)
    {
        $mangoPayApi = $this->init();

        $date = new \DateTime();
        $message = 'Payout created at ' . $date->format('d/m/Y H:i') . ' with ' . ($wallet->Balance->Amount / 100) . ' euros';

        $payout = new PayOut();
        $payout->Tag = $message;
        $payout->AuthorId = $tipster->getMangoPayId();
        $payout->DebitedWalletId = $tipster->getMangoPayWalletId();
        $payout->DebitedFunds = new Money();
        $payout->DebitedFunds->Currency = $wallet->Balance->Currency;
        $payout->DebitedFunds->Amount = $wallet->Balance->Amount;
        $payout->Fees = new Money();
        $payout->Fees->Currency = 'EUR';
        $payout->Fees->Amount = 0;
        $payout->PaymentType = PayOutPaymentType::BankWire;
        $payout->MeanOfPaymentDetails = new PayOutPaymentDetailsBankWire();
        $payout->MeanOfPaymentDetails->BankAccountId = $tipster->getMangoPayBankAccountId();

        $result = $mangoPayApi->PayOuts->Create($payout);

        $this->logger->info($message);

        return $result;
    }

    /**
     * @param $walletId
     * @return Wallet
     */
    public function viewWallet($walletId)
    {
        $mangoPayApi = $this->init();
        $result = $mangoPayApi->Wallets->Get($walletId);
        return $result;
    }

    /**
     * @param Transfer $transfer
     * @return Transfer
     */
    public function createTransfer(Transfer $transfer)
    {
        $mangoPayApi = $this->init();
        $createdTransfer = $mangoPayApi->Transfers->Create($transfer);

        if ($createdTransfer->Status === PayInStatus::Succeeded) {
            $this->logger->info('transfer done!', [
                'transferId' => $createdTransfer->Id
            ]);
        } else {
            $this->logger->critical('transfer error!', [
                'TransferId' => $createdTransfer->Id,
                'ResultCode' => $createdTransfer->ResultCode,
                'ResultMessage' => $createdTransfer->ResultMessage,
            ]);
        }

        return $createdTransfer;
    }

    /**
     * @param $userId
     * @param $bankAccountId
     * @return BankAccount
     */
    public function getBankAccount($userId, $bankAccountId)
    {
        $mangoPayApi = $this->init();
        $result = $mangoPayApi->Users->GetBankAccount($userId, $bankAccountId);
        return $result;
    }

    /**
     * @param $data
     * @param $userId
     * @return null|string
     */
    public function createBankAccount($data, $userId)
    {
        $mangoPayApi = $this->init();

        $bankAccount = new BankAccount();
        $bankAccount->Type = 'IBAN';
        $bankAccount->Details = new BankAccountDetailsIBAN();
        $bankAccount->Details->IBAN = $data['iban'];
        $bankAccount->Details->BIC = $data['bic'];
        $bankAccount->OwnerName = $data['ownerName'];
        $bankAccount->OwnerAddress = new Address();
        $bankAccount->OwnerAddress->AddressLine1 = $data['addressLine1'];
        $bankAccount->OwnerAddress->AddressLine2 = $data['addressLine2'];
        $bankAccount->OwnerAddress->PostalCode = $data['postalCode'];
        $bankAccount->OwnerAddress->City = $data['city'];
        $bankAccount->OwnerAddress->Country = $data['country']->getAlpha2();
        $bankAccount->OwnerAddress->Region = '';

        $result = $mangoPayApi->Users->CreateBankAccount($userId, $bankAccount);

        $this->logger->info(json_encode($result));

        return $result->Id;
    }

    /**
     * @param $userId
     * @param $description
     * @return Wallet
     */
    public function createWallet($userId, $description = '')
    {
        $mangoPayApi = $this->init();

        $wallet = new Wallet();
        $wallet->Owners = [$userId];
        $wallet->Currency = 'EUR';
        $wallet->Description = $description;
        $createdWallet = $mangoPayApi->Wallets->Create($wallet);

        return $createdWallet;
    }

    /**
     * @param $userId
     * @return \MangoPay\UserLegal|UserNatural
     */
    public function viewUser($userId)
    {
        $mangoPayApi = $this->init();
        $user = $mangoPayApi->Users->Get($userId);

        return $user;
    }

    public function updateUser(User $user, $userId)
    {
        $mangoPayApi = $this->init();

        $userNatural = $this->viewUser($userId);

        $userNatural->FirstName = $user->getFirstName();
        $userNatural->LastName = $user->getLastName();
        $userNatural->Email = $user->getEmail();
        $userNatural->Birthday = $user->getBirthday()->getTimestamp();
        $userNatural->Nationality = $user->getNationality()->getAlpha2();
        $userNatural->CountryOfResidence = $user->getCountry()->getAlpha2();
        $userNatural->Address = new Address();
        $userNatural->Address->AddressLine1 = $user->getAddressLine1();
        $userNatural->Address->AddressLine2 = $user->getAddressLine2();
        $userNatural->Address->PostalCode = $user->getPostalCode();
        $userNatural->Address->City = $user->getCity();
        $userNatural->Address->Country = $user->getCountry()->getAlpha2();
        $userNatural->Occupation = $user->getOccupation();
        $userNatural->IncomeRange = $user->getIncomeRange();

        $mangoPayApi->Users->Update($userNatural);
    }

    /**
     * @param User $user
     * @param MangoPayApi|null $mangoPayApi
     * @return \MangoPay\UserLegal
     */
    public function createUser(User $user, MangoPayApi $mangoPayApi = null)
    {
        if ($mangoPayApi === null) {
            $mangoPayApi = $this->init();
        }

        $userNatural = new UserNatural();
        $userNatural->FirstName = $user->getFirstName();
        $userNatural->LastName = $user->getLastName();
        $userNatural->Email = $user->getEmail();
        $userNatural->Birthday = $user->getBirthday()->getTimestamp();
        $userNatural->Nationality = $user->getNationality()->getAlpha2();
        $userNatural->CountryOfResidence = $user->getCountry()->getAlpha2();

        if ($user->getAddressLine1() && $user->getPostalCode() && $user->getCity()) {
            $userNatural->Address = new Address();
            $userNatural->Address->AddressLine1 = $user->getAddressLine1();
            $userNatural->Address->AddressLine2 = $user->getAddressLine2();
            $userNatural->Address->PostalCode = $user->getPostalCode();
            $userNatural->Address->City = $user->getCity();
            $userNatural->Address->Country = $user->getCountry()->getAlpha2();
            $userNatural->Occupation = $user->getOccupation();
            $userNatural->IncomeRange = $user->getIncomeRange();
        }

        $createdUser = $mangoPayApi->Users->Create($userNatural);

        $this->logger->info('MangoPay User created!', [
            'user' => json_encode($userNatural),
            'createdUser' => json_encode($createdUser),
        ]);

        return $createdUser;
    }

    /**
     * @param Subscription $subscription
     * @return CardRegistration $createdCardRegister
     */
    public function createCardRegister(Subscription $subscription)
    {
        $mangoPayApi = $this->init();

        // Get user
        if ($subscription->getUser()->getMangoPayId() === null) {

            $createdUser = $this->createUser($subscription->getUser(), $mangoPayApi);

            // Persist ID
            $subscription->getUser()->setMangoPayId($createdUser->Id);
            $this->em->persist($subscription->getUser());
            $this->em->flush();

        } else {
            $createdUser = $mangoPayApi->Users->Get($subscription->getUser()->getMangoPayID());
        }

        // register card
        $cardRegister = new CardRegistration();
        $cardRegister->UserId = $createdUser->Id;
        $cardRegister->Currency = 'EUR';
        $cardRegister->CardType = 'CB_VISA_MASTERCARD';

        $createdCardRegister = $mangoPayApi->CardRegistrations->Create($cardRegister);

        return $createdCardRegister;

    }

    public function payment($data, $errorCode)
    {
        $userLogged = $this->tokenStorage->getToken()->getUser();

        $mangoPayApi = $this->init();

        // get the subscription
        $subscriptionId = $this->session->get('subscriptionId');
        $subscription = $this->em->getRepository('AppBundle:Subscription')
            ->findOneById($subscriptionId);

        if ($subscription === null) {
            $message = "Subscription not found! (ID: ".$subscriptionId.")";
            throw new \Exception($message);
            $this->logger->critical($message);
        }

        // create payment
        $payment = new Payment();
        $payment->setRawData($data)
            ->setErrorCode($errorCode)
            ->setType(PaymentType::Mangopay)
            ->setSubscription($subscription);
        $this->em->persist($payment);
        $this->em->flush();

        // cancel the subscription by default
        $subscription->setStatus(SubscriptionStatus::Cancel)
            ->setUpdatedAt(new \DateTime());
        $this->em->persist($subscription);
        $this->em->flush();

        try {
            // update register card with registration data from Payline service
            $cardRegisterId = $this->session->get('cardRegisterId');
            $cardRegister = $mangoPayApi->CardRegistrations->Get($cardRegisterId);
            $cardRegister->RegistrationData = $data !== null ? 'data=' . $data : 'errorCode=' . $errorCode;
            $updatedCardRegister = $mangoPayApi->CardRegistrations->Update($cardRegister);

            if ($updatedCardRegister->Status !== CardRegistrationStatus::Validated || !isset($updatedCardRegister->CardId)) {
                $this->logger->error(
                    'Cannot create card. Payment has not been created.',
                    [
                        'type' => 'MangoPay CardRegistration',
                        'cardRegisterId' => $cardRegisterId,
                        'status' => $updatedCardRegister->Status,
                        'cardId' => $updatedCardRegister->CardId,
                        'data' => $data,
                        'errorCode' => $errorCode,
                        'getCardRegisterId' => $cardRegister->Id
                    ]
                );
                return false;
            }

            // get created virtual card object
            $card = $mangoPayApi->Cards->Get($updatedCardRegister->CardId);

            // create temporary wallet for user
            if ($userLogged->getMangoPayWalletId() === null) {
                $createdWallet = $this->createWallet($userLogged->getMangoPayId(), 'Member subscription wallet');
                $userLogged->setMangoPayWalletId($createdWallet->Id);
            }


            // create pay-in CARD DIRECT
            $payIn = new PayIn();
            $payIn->CreditedWalletId = $userLogged->getMangoPayWalletId();
            $payIn->AuthorId = $updatedCardRegister->UserId;
            $payIn->DebitedFunds = new Money();
            $payIn->DebitedFunds->Amount = round($subscription->getAmount() * 100);
            $payIn->DebitedFunds->Currency = 'EUR';
            $payIn->Fees = new Money();
            $payIn->Fees->Amount = 0;
            $payIn->Fees->Currency = 'EUR';

            // payment type as CARD
            $payIn->PaymentDetails = new PayInPaymentDetailsCard();
            $payIn->PaymentDetails->CardType = $card->CardType;
            $payIn->PaymentDetails->CardId = $card->Id;

            // execution type as DIRECT
            $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
            $payIn->ExecutionDetails->SecureModeReturnURL = $this->router->generate('payment_success', [], 0);

            // Activate 3D-Secure
            //$payIn->ExecutionDetails->SecureMode = 'FORCE';

            // create Pay-in
            $createdPayIn = $mangoPayApi->PayIns->Create($payIn);

            // check 3D-Secure
            if ($createdPayIn->ExecutionDetails->SecureModeNeeded && $createdPayIn->Status!=PayInStatus::Failed) {
                return new RedirectResponse($createdPayIn->ExecutionDetails->SecureModeRedirectURL);
            }

            if ($createdPayIn->Status === PayInStatus::Succeeded) {

                // create payment
                $payment->setStatus($createdPayIn->Status)
                    ->setJsonData(json_encode($createdPayIn))
                    ->setResultCode($createdPayIn->ResultCode)
                    ->setTransactionId($createdPayIn->Id)
                    ->setUpdatedAt(new \DateTime());
                $this->em->persist($payment);
                $this->em->flush();

                // update subscription
                $subscription->setUpdatedAt(new \DateTime())
                    ->setStatus(SubscriptionStatus::Vip);
                $this->em->persist($subscription);
                $this->em->flush();

                return true;
            } else {

                $payment->setStatus($createdPayIn->Status)
                    ->setJsonData(json_encode($createdPayIn))
                    ->setResultCode($createdPayIn->ResultCode)
                    ->setTransactionId($createdPayIn->Id)
                    ->setUpdatedAt(new \DateTime());
                $this->em->persist($payment);
                $this->em->flush();

                $this->logger->error(
                    'Pay-In has been created with status: '
                    . $createdPayIn->Status . ' (result code: '
                    . $createdPayIn->ResultCode . ')'
                );
            }
        } catch (ResponseException $e) {
            $this->logger->alert(
                $e->getMessage(),
                [
                    'type' => 'MangoPay ResponseException',
                    'code' => $e->getCode(),
                    'details' => $e->GetErrorDetails()
                ]
            );
        }

        return false;

    }

}