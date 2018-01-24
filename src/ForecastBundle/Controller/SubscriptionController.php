<?php

namespace ForecastBundle\Controller;

use AppBundle\Entity\BankWire;
use AppBundle\Entity\PaymentType;
use AppBundle\Entity\Payout;
use ForecastBundle\Form\BankAccountType;
use ForecastBundle\Form\PaymentAccountType;
use MangoPay\KycDocumentType;
use MangoPay\PayInStatus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubscriptionController
 * @package ForecastBundle\Controller
 * @Route("forecast/")
 */
class SubscriptionController extends Controller
{
    /**
     * @Route("account-setting", name="forecast_subscription_account_setting")
     */
    public function accountSettingAction()
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $tipster = $userLogged->getTipster();

        $user = null;
        $identityProof = null;
        $addressProof = null;

        $mangoPayService = $this->get('mangopay');

        if ($tipster->getMangoPayId() !== null) {
            $user = $mangoPayService->viewUser($tipster->getMangoPayId());
        }

        if ($tipster->getMangoPayIdentityProofId() !== null) {
            $identityProof = $mangoPayService->viewKYCDocument($tipster->getMangoPayIdentityProofId());
        }

        if ($tipster->getMangoPayAddressProofId() !== null) {
            $addressProof = $mangoPayService->viewKYCDocument($tipster->getMangoPayAddressProofId());
        }



        return $this->render('ForecastBundle:Subscription:account_setting.html.twig', array(
            'user' => $user,
            'tipster' => $tipster,
            'identityProof' => $identityProof,
            'addressProof' => $addressProof,
        ));
    }

    /**
     * @Route("create-payment-account", name="forecast_subscription_create_payment_account")
     */
    public function createPaymentAccountAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        if ($userLogged->getTipster()->getMangoPayId() !== null) {
            return $this->redirectToRoute('forecast_subscription_account_setting');
        }

        // Check user invoice information
        if ($userLogged->checkUserInvoice() === false) {
            $form = $this->createForm(PaymentAccountType::class, $userLogged);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($userLogged);
                $em->flush();
            } else {
                return $this->render('ForecastBundle:Subscription:create_payment_account.html.twig', array(
                    'form' => $form->createView()
                ));
            }
        }

        $mangoPayService = $this->get('mangopay');
        $createdUser = $mangoPayService->createUser($userLogged);
        $createdWallet = $mangoPayService->createWallet($createdUser->Id, 'Tipster wallet');
        $userLogged->getTipster()->setMangoPayId($createdUser->Id);
        $userLogged->getTipster()->setMangoPayWalletId($createdWallet->Id);
        $userLogged->getTipster()->setMangoPayCreatedAt(new \DateTime());
        $em->persist($userLogged->getTipster());
        $em->flush();

        return $this->redirectToRoute('forecast_subscription_account_setting');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("edit-payment-account", name="forecast_subscription_edit_payment_account")
     */
    public function editPaymentAccountAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(PaymentAccountType::class, $userLogged);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($userLogged);
            $em->flush();

            $mangoPayService = $this->get('mangopay');
            $mangoPayService->updateUser($userLogged, $userLogged->getTipster()->getMangoPayId());

            return $this->redirectToRoute('forecast_subscription_account_setting');
        }

        return $this->render('ForecastBundle:Subscription:edit_payment_account.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("bank-account-setting", name="forecast_bank_account_setting")
     */
    public function bankAccountSettingAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $tipster = $userLogged->getTipster();

        if ($tipster->getMangoPayId() === null) {
            return $this->redirectToRoute('forecast_subscription_account_setting');
        }

        $form = $this->createForm(BankAccountType::class, [
            'ownerName' => $userLogged->getLastName() . ' ' . $userLogged->getFirstName()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $mangoPayService = $this->get('mangopay');
            $bankAccountId = $mangoPayService->createBankAccount($data, $tipster->getMangoPayId());

            $tipster->setMangoPayBankAccountId($bankAccountId);
            $em = $this->getDoctrine()->getManager();
            $em->persist($tipster);
            $em->flush();

        } else {
            if ($tipster->getMangoPayBankAccountId() === null) {
                return $this->render('ForecastBundle:Subscription:bank_account_setting.html.twig', array(
                    'form' => $form->createView()
                ));
            }
        }

        $mangoPayService = $this->get('mangopay');
        $bankAccount = $mangoPayService->getBankAccount($tipster->getMangoPayId(), $tipster->getMangoPayBankAccountId());

        return $this->render('ForecastBundle:Subscription:bank_account_setting.html.twig', array(
            'bankAccount' => $bankAccount
        ));

    }

    /**
     * @Route("edit-bank-account", name="forecast_edit_bank_account")
     */
    public function editBankAccountAction()
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        $form = $this->createForm(BankAccountType::class, [
            'ownerName' => $userLogged->getLastName() . ' ' . $userLogged->getFirstName()
        ]);

        return $this->render('ForecastBundle:Subscription:edit_bank_account.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("payout-setting", name="forecast_payout_setting")
     */
    public function payoutSettingAction()
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $tipster = $userLogged->getTipster();

        if ($tipster->getMangoPayId() === null) {
            return $this->redirectToRoute('forecast_subscription_account_setting');
        }

        if ($tipster->getMangoPayBankAccountId() === null) {
            return $this->redirectToRoute('forecast_bank_account_setting');
        }

        $mangoPayService = $this->get('mangopay');
        $wallet = $mangoPayService->viewWallet($tipster->getMangoPayWalletId());

        $payouts = [];
        foreach ($tipster->getBankWires() as $bankWire) {
            $payouts[] = $mangoPayService->viewPayout($bankWire->getMangoPayPayoutId());
        }

        return $this->render('ForecastBundle:Subscription:payout_setting.html.twig', array(
            'wallet' => $wallet,
            'payouts' => $payouts
        ));
    }

    /**
     * @Route("create-payout", name="forecast_create_payout")
     */
    public function createPayoutAction()
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $tipster = $userLogged->getTipster();

        $mangoPayService = $this->get('mangopay');

        $wallet = $mangoPayService->viewWallet($tipster->getMangoPayWalletId());

        if ($wallet->Balance->Amount === 0) {
            return $this->redirectToRoute('forecast_payout_setting');
        }

        $payout = $mangoPayService->createPayout($tipster, $wallet);

        $bankWire = new BankWire();
        $bankWire->setMangoPayPayoutId($payout->Id)
            ->setCreatedAt(new \DateTime())
            ->setType(PaymentType::Mangopay)
            ->setTipster($tipster);

        $em = $this->getDoctrine()->getManager();
        $em->persist($bankWire);
        $em->flush();

        if ($payout->Status === PayInStatus::Failed) {
            $logger = $this->get('logger');
            $logger->critical('Payout failed!', [
                'Id' => $payout->Id,
                'AuthorId' => $payout->AuthorId,
                'BankAccountId' => $payout->MeanOfPaymentDetails->BankAccountId,
                'DebitedWalletId' => $payout->DebitedWalletId,
                'ResultMessage' => $payout->ResultMessage,
                'ResultCode' => $payout->ResultCode
            ]);

            // needs to be KYC verified
            if ($payout->ResultCode === '002998' || $payout->ResultCode === '002999') {
                $tipster->setCheckKYC(true);
                $em->persist($tipster);
                $em->flush();
            }
        }

        return $this->render('ForecastBundle:Subscription:create_payout.html.twig', array(
            'payout' => $payout
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("upgrade-payment-account", name="forecast-upgrade-payment-account")
     */
    public function upgradePaymentAccountAction()
    {
        return $this->redirectToRoute('forecast_subscription_account_setting');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("submit-document", name="forecast_subscription_submit_document")
     */
    public function submitDocumentAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $tipster = $userLogged->getTipster();

        $type = $request->get('kyc_type');
        $document = $request->files->get('kyc_document');

        if (in_array($document->getMimeType(), ['application/pdf', 'image/jpg', 'image/jpeg', 'image/png', 'image/gif'])) {
            if ($document->getSize() < 7 * 1000 * 1000 && $document->getSize() > 1000) {
                $mangoPayService = $this->get('mangopay');
                $kycDocument = $mangoPayService->submitKYCDocument($document->getRealPath(), $type, $tipster->getMangoPayId());

                $em = $this->getDoctrine()->getManager();
                if ($type === KycDocumentType::IdentityProof) {
                    $tipster->setMangoPayIdentityProofId($kycDocument->Id);
                } else {
                    $tipster->setMangoPayAddressProofId($kycDocument->Id);
                }
                $em->persist($tipster);
                $em->flush();
            }
        }

        return $this->redirectToRoute('forecast_subscription_account_setting');
    }

}
