<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Subscriber;
use AppBundle\Entity\User;
use AppBundle\Form\SubscriberType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubscriberController
 * @package AppBundle\Controller
 *
 * @Route("newsletter")
 */
class SubscriberController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/subscribe", name="subscriber_subscribe")
     */
    public function subscribeAction(Request $request)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('title.newsletter_subscription');

        $subscriber = new Subscriber();

        $form = $this->createForm(SubscriberType::class, $subscriber);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            $email = $request->get('email');
            if ($email === null) {
                if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                    $userLogged = $this->get('security.token_storage')->getToken()->getUser();
                    $email = $userLogged->getEmail();
                }
            }
            $form->get('email')->setData($email);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // check already existing email address
            $existingSubscriber = $em->getRepository('AppBundle:Subscriber')
                ->findOneByEmail($subscriber->getEmail());

            if ($existingSubscriber !== null) {
                $existingSubscriber->setSubscribedAt(new \DateTime())
                    ->setPartners($subscriber->getPartners())
                    ->setUnsubscribedAt(null);
                $subscriber = $existingSubscriber;
            }

            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $userLogged = $this->get('security.token_storage')->getToken()->getUser();
                $subscriber->setEmailValid($userLogged->getEmailValid());
            }

            $em->persist($subscriber);
            $em->flush();

            // sent a confirm email
            if ($subscriber->getEmailValid() === false) {
                // Send the confirm email.
                $subject = sprintf("%s - %s",
                    $this->container->getParameter('app_name'),
                    $this->get('translator')->trans('title.newsletter_subscription'));
                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($this->container->getParameter('mailer_sender_address'))
                    ->setTo($subscriber->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@App/Emails/newsletter_confirm_email.html.twig',
                            array('subscriber' => $subscriber)
                        ),
                        'text/html'
                    );
                $code = $this->get('mailer')->send($message);
            }

            return $this->render('AppBundle:Subscriber:subscribe_success.html.twig', [
                'subscriber' => $subscriber
            ]);
        }

        return $this->render('AppBundle:Subscriber:subscribe.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @param Subscriber $subscriber
     * @param $email
     *
     * @return Response
     *
     * @Route("/confirm-email/{id}-{email}", name="subscriber_confirm_email")
     */
    public function confirmEmailAction(Subscriber $subscriber, $email)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.newsletter_confirm');

        if ($subscriber->getEmail() !== $email) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('error.user_confirm_email')
            );
        }

        $subscriber->setEmailValid(true);

        $em = $this->getDoctrine()->getManager();
        $em->persist($subscriber);
        $em->flush();

        return $this->render('AppBundle:Subscriber:confirm_email.html.twig');
    }

    /**
     * @Route("/unsubscribe/{email}", name="subscriber_unsubscribe")
     */
    public function unsubscribeAction(Subscriber $subscriber)
    {

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.newsletter_unsubscribe');

        $subscriber->setUnsubscribedAt(new \DateTime());
        $em = $this->getDoctrine()->getManager();

        $em->persist($subscriber);
        $em->flush();

        return $this->render('AppBundle:Subscriber:unsubscribe.html.twig');
    }

}
