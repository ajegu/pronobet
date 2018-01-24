<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Role;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\User;
use AppBundle\Entity\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * User controller.
 *
 * @Route("user")
 */
class UserController extends Controller
{
    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('title.new_user');

        $user = new User();
        $form = $this->createForm('AppBundle\Form\UserType', $user, array(
            'translator' => $this->get('translator')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Encode manually the password with bcrypt.
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);

            $user->setRole('ROLE_MEMBER');

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // check newsletter subscription
            if ($user->getConfirm()) {

                $subscriber = $em->getRepository('AppBundle:Subscriber')
                    ->findOneByEmail($user->getEmail());

                if ($subscriber === null) {
                    $subscriber = new Subscriber();
                    $subscriber->setEmail($user->getEmail())
                        ->setPartners($user->getPartners());
                } else {
                    $subscriber->setSubscribedAt(new \DateTime())
                        ->setUnsubscribedAt(null)
                        ->setPartners($user->getPartners());
                }

                $em->persist($subscriber);
                $em->flush();
            }

            // Send the confirm email.
            $subject = sprintf("%s - %s",
                $this->container->getParameter('app_name'),
                $this->get('translator')->trans('title.confirm_new_user'));
            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($this->container->getParameter('mailer_sender_address'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        '@App/Emails/confirm_new_user.html.twig',
                        array('user' => $user)
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            // authenticate the user
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'my_user_provider', ['ROLE_MEMBER']);
            $this->get("security.token_storage")->setToken($token);

            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            $session = $this->get('session');
            if ($session->get('redirect')) {
                return $this->redirect($session->get('redirect'));
            } else {
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('AppBundle:User:new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'error' => null
        ));
    }

    /**
     * Confirm the user email
     *
     * @param User $user
     * @param $email
     *
     * @return Response
     *
     * @Route("/confirm-email/{id}-{email}", name="user_confirm_email")
     * @Method("GET")
     */
    public function confirmEmailAction(User $user, $email)
    {
        if ($user->getEmail() !== $email) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('error.user_confirm_email')
            );
        }

        $user->setEmailValid(true);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        // valid subscriber email
        $subscriber = $em->getRepository('AppBundle:Subscriber')
            ->findOneByEmail($user->getEmail());

        if ($subscriber !== null) {
            $subscriber->setEmailValid(true);
            $em->persist($subscriber);
            $em->flush();
        }

        return $this->render('AppBundle:User:confirm_email.html.twig');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/tipster-subscription", name="user_tipster_subscription")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function tipsterSubscriptionAction(Request $request)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.my_tipsters');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        return $this->render('AppBundle:User:tipster_subscription.html.twig', [
            'user' => $userLogged
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/newsletter-subscription", name="user_newsletter_subscription")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function newsletterSubscriptionAction()
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.newsletter_subscription');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        $subscriber = $this->getDoctrine()->getRepository('AppBundle:Subscriber')
            ->findOneByEmail($userLogged->getEmail());

        if ($subscriber !== null && $subscriber->getUnsubscribedAt() !== null) {
            $subscriber = null;
        }

        return $this->render('AppBundle:User:newsletter_subscription.html.twig', [
            'subscriber' => $subscriber,
            'user' => $userLogged
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/payment-history", name="user_payment_history")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function paymentHistoryAction()
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.payment_history');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        $payments = [];

        foreach ($userLogged->getSubscriptions() as $subscription) {
            $subscriptionPayments = $this->getDoctrine()->getRepository('AppBundle:Payment')
                ->findBySubscription($subscription);
            foreach ($subscriptionPayments as $subscriptionPayment) {
                $payments[] = $subscriptionPayment;
            }
        }

        return $this->render('AppBundle:User:payment_history.html.twig', [
            'payments' => $payments
        ]);
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit/{id}", name="user_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function editAction(Request $request, User $user)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('title.user_edit');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($userLogged->getId() !== $user->getId()) {
            return $this->redirectToRoute('user_show', array('id' => $userLogged->getId()));
        }

        $editForm = $this->createForm(
            'AppBundle\Form\UserType',
            $user,
            array('translator' => $this->get('translator'))
        );
        $editForm->remove('termsAccepted');

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            // Encode manually the password with bcrypt.
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush($user);

            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        return $this->render('AppBundle:User:edit.html.twig', array(
            'user' => $user,
            'form' => $editForm->createView(),
            'error' => array()
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="user_show")
     * @Method("GET")
     */
    public function showAction()
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.user_personal_data');

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($userLogged === null) {
            return $this->redirectToRoute('login');
        }

        return $this->render('AppBundle:User:show.html.twig', array(
            'user' => $userLogged
        ));
    }
}
