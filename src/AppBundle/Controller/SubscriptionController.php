<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Form\SubscriptionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SubscriptionController
 * @package AppBundle\Controller
 *
 * @Route("subscription")
 */
class SubscriptionController extends Controller
{
    /**
     * @param Tipster $tipster
     * @return Response
     *
     * @Route("/subscribe/{id}", name="subscription_subscribe")
     */
    public function subscribeAction(Tipster $tipster)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.tipster_list', $this->get("router")->generate("tipster_index"));
        $breadcrumbs->addItem('label.tipster_show', $this->get('router')->generate('tipster_show',['id' => $tipster->getId()]));
        $breadcrumbs->addItem('label.subscription');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        // check subscription
        $existingSubscription = $em->getRepository('AppBundle:Subscription')
            ->findOneBy([
                'user' => $userLogged,
                'tipster' => $tipster,
                'status' => SubscriptionStatus::Vip,
                'activate' => true
            ]);

        if ($existingSubscription !== null) {
            return $this->render('AppBundle:Subscription:existing_subscription.html.twig', ['subscription' => $existingSubscription]);
        }

        $createdAt = new \DateTime();
        $finishedAt = new \DateTime();
        $finishedAt->add(new \DateInterval("P1M"));

        return $this->render('AppBundle:Subscription:subscribe.html.twig', array(
            'tipster' => $tipster,
            'createdAt' => $createdAt,
            'finishedAt' => $finishedAt
        ));
    }

    /**
     * @param Tipster $tipster
     * @return Response
     *
     * @Route("/subscribe-for-free/{id}", name="subscription_subscribe_for_free")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function subscribeForFreeAction(Tipster $tipster)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.tipster_list', $this->get("router")->generate("tipster_index"));
        $breadcrumbs->addItem('label.tipster_show', $this->get('router')->generate('tipster_show', ['id' => $tipster->getId()]));
        $breadcrumbs->addItem('title.subscribe_free', $this->get("router")->generate("tipster_index"));

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        // check subscription
        $existingSubscription = $em->getRepository('AppBundle:Subscription')
            ->findOneBy([
                'user' => $userLogged,
                'tipster' => $tipster,
                'status' => SubscriptionStatus::Free
            ]);

        if ($existingSubscription !== null) {
            return $this->render('AppBundle:Subscription:existing_subscription.html.twig', ['subscription' => $existingSubscription]);
        }

        $subscription = new Subscription($tipster, $userLogged);
        $subscription->setStatus(SubscriptionStatus::Free)
            ->setEmailNotification(true)
            ->setSmsNotification(false)
            ->setActivate(true)
            ->setFees(0)
            ->setAmount(0);

        $em->persist($subscription);
        $em->flush();

        return $this->render('AppBundle:Subscription:subscribe_for_free.html.twig', array(
            'tipster' => $tipster
        ));
    }

    /**
     * @param Request $request
     * @param Subscription $subscription
     * @return Response
     *
     * @Route("/edit-notifications/{id}", name="subscription_edit_notifications")
     */
    public function editNotificationsAction(Request $request, Subscription $subscription)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        $form = $this->createForm(SubscriptionType::class, $subscription);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            if ($subscription->getSmsNotification()) {
                // check phone number
                $data = $request->request->get('appbundle_subscription');
                if ($data['phoneNumber'] === '') {

                    $translator = $this->get('translator');
                    $error = new FormError($translator->trans('error.subscription_phone_number_required'));
                    $form->get('phoneNumber')->addError($error);

                    return $this->render('AppBundle:Subscription:edit_notifications.html.twig', array(
                        'form' => $form->createView(),
                        'subscription' => $subscription
                    ));
                } else {
                    // persist phone number
                    $userLogged->setPhoneNumber($data['phoneNumber']);
                    $em->persist($userLogged);
                    $em->flush();
                }
            }

            $em->persist($subscription);
            $em->flush();

            return $this->redirectToRoute('user_tipster_subscription');
        } else {
            $form->get('phoneNumber')->setData($userLogged->getPhoneNumber());
        }

        return $this->render('AppBundle:Subscription:edit_notifications.html.twig', array(
            'form' => $form->createView(),
            'subscription' => $subscription
        ));
    }

    /**
     * @param Tipster $tipster
     * @return Response
     *
     * @Route("/unsubscribe/{id}", name="subscription_unsubscribe")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function unsubscribeAction(Tipster $tipster)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $subscription = $em->getRepository('AppBundle:Subscription')
            ->findOneBy([
                'tipster' => $tipster,
                'user' => $userLogged,
                'status' => SubscriptionStatus::Free
            ]);

        if ($subscription !== null) {
            $em->remove($subscription);
            $em->flush();
        }

        return $this->redirectToRoute('user_tipster_subscription');
    }

}
