<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use AppBundle\Form\UserInvoiceType;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payee;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentOptions;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class PaymentController
 * @package AppBundle\Controller
 * @Route("payment")
 */
class PaymentController extends Controller
{

    /**
     * @Route("/checkout/{id}", name="payment_checkout")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function checkoutAction(Request $request, Tipster $tipster)
    {

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $session = $this->get('session');
        $em = $this->getDoctrine()->getManager();

        // calculate subscription dates
        $createdAt = new \DateTime();
        $finishedAt = new \DateTime();
        $finishedAt->add(new \DateInterval("P1M"));

        // calculate fees
        $fees = round($tipster->getCommission() * $tipster->getFee() / 100 * 1000) / 1000;

        $subscription = new Subscription($tipster, $userLogged);
        $subscription->setCreatedAt($createdAt)
            ->setAmount($tipster->getFee())
            ->setFees($fees)
            ->setEmailNotification(true)
            ->setSmsNotification(false)
            ->setStatus(SubscriptionStatus::Pending)
            ->setActivate(true)
            ->setFinishedAt($finishedAt);

        // check existing pending subscription
        $oldSubscription = $em->getRepository('AppBundle:Subscription')
            ->findOneBy([
                'tipster' => $tipster,
                'user' => $userLogged,
                'status' => SubscriptionStatus::Pending
            ]);
        if ($oldSubscription !== null) {
            $em->remove($oldSubscription);
            $em->flush();
        }

        // save subscription
        $em->persist($subscription);
        $em->flush();

        $session->set('subscriptionId', $subscription->getId());

        // Check user invoice information
        if ($userLogged->checkUserInvoice() === false) {
            return $this->redirectToRoute('payment_user_invoice');
        }

        // Create transaction
        $mangoPayService = $this->get('mangopay');
        $card = $mangoPayService->createCardRegister($subscription);

        // Store data in session for processing payment
        $session->set('cardRegisterId', $card->Id);

        return $this->render('AppBundle:Payment:checkout.html.twig', array(
            'subscription' => $subscription,
            'card' => $card
        ));
    }

    /**
     * @Route("/process", name="payment_process")
     */
    public function processAction(Request $request)
    {
        $data = $request->get('data');
        $errorCode = $request->get('errorCode');

        $mangoPayService = $this->get('mangopay');

        $result = $mangoPayService->payment($data, $errorCode);

        if ($result === false) {
            return $this->redirectToRoute('payment_failure');
        }

        if ($result === true) {
            return $this->redirectToRoute('payment_success');
        }

        if (get_class($result) === RedirectResponse::class) {
            return $result;
        }

        return $this->redirectToRoute('payment_failure');
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @Route("/user-invoice", name="payment_user_invoice")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     * @throws \Exception
     */
    public function userInvoiceAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(UserInvoiceType::class, $userLogged);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($userLogged);
            $em->flush();

            $session = $this->get('session');
            $subscriptionId = $session->get('subscriptionId');
            $subscription = $this->getDoctrine()->getManager()->getRepository('AppBundle:Subscription')
                ->find($subscriptionId);

            if ($subscription === null) {
                throw new \Exception("Subscription not found! (ID: ".$subscriptionId.")");
            }

            return $this->redirectToRoute('payment_checkout', ['id' => $subscription->getTipster()->getId()]);
        }

        return $this->render('AppBundle:Payment:user_invoice.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/success", name="payment_success")
     *
     */
    public function successAction(Request $request)
    {

        $session = $this->get('session');
        $subscriptionId = $session->get('subscriptionId');
        if ($subscriptionId === null) {
            throw new \Exception("SubscriptionId not found!");
        }

        $subscription = $this->getDoctrine()->getManager()->getRepository('AppBundle:Subscription')
            ->find($subscriptionId);

        if ($subscription === null) {
            throw new \Exception("Subscription not found! (ID: ".$subscriptionId.")");
        }

        return $this->render('AppBundle:Payment:success.html.twig', [
            'subscription' => $subscription
        ]);
    }

    /**
     * @Route("/failure", name="payment_failure")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function failureAction()
    {
        $session = $this->get('session');
        $subscription = null;
        $subscriptionId = $session->get('subscriptionId');
        if ($subscriptionId !== null) {
            $subscription = $this->getDoctrine()->getRepository('AppBundle:Subscription')
                ->find($subscriptionId);
        }

        return $this->render('AppBundle:Payment:failure.html.twig', [
            'subscription' => $subscription
        ]);
    }

}
