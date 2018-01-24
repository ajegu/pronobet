<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\LoginType;
use AppBundle\Form\ResetPasswordType;
use AppBundle\Form\UserEmailType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastEmail = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class, null, [
            'redirect' => $this->get('session')->get('redirect')
        ]);

        return $this->render('@App/security/login.html.twig', array(
            'last_email' => $lastEmail,
            'error' => $error,
            'form' => $form->createView()
        ));
    }

    /**
     *
     * @Route("/reset-password", name="reset_password")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetPasswordAction(Request $request)
    {

        $error = false;
        $form = $this->createForm(UserEmailType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            // Fetch user.
            $userList = $this->getDoctrine()->getRepository('AppBundle:User')
                ->findByEmail($email);

            if (count($userList) === 1) {
                $user = $userList[0];

                // Create reset token.
                $token = bin2hex(random_bytes(30));

                // Persist the token.
                $user->setResetPasswordToken($token);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush($user);

                // Send email.
                $subject = sprintf("%s - %s",
                    $this->container->getParameter('app_name'),
                    $this->get('translator')->trans('title.reset_password'));
                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($this->container->getParameter('mailer_sender_address'))
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@App/Emails/reset_password.html.twig',
                            array('token' => $token)
                        ),
                        'text/html'
                    );
                $this->get('mailer')->send($message);

                // Log the request.
                $logger = $this->get('logger');
                $logger->notice('Reset Password Action', array(
                    'email' => $user->getEmail(),
                    'token' => $token
                ));

                // Redirect to success page.
                return $this->render(
                    '@App/security/reset_password_success.html.twig',
                    array(
                        'email' => $user->getEmail(),
                        'token' => $token
                    )
                );
            } else {
                $error = $this->get('translator')->trans('error.user_not_found');
            }
        }

        return $this->render('@App/security/reset_password.html.twig', array(
            'form' => $form->createView(),
            'error' => $error
        ));
    }

    /**
     * @Route("/confirm_reset_password/{token}", name="confirm_reset_password")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function confirmResetPassword(Request $request, $token)
    {
        $error = false;

        $form = $this->createForm(ResetPasswordType::class, null, array(
            'translator' => $this->get('translator')
        ));

        // Search user with token.
        $userList = $this->getDoctrine()->getRepository('AppBundle:User')
            ->findByResetPasswordToken($token);

        if (count($userList) === 1) {
            $user = $userList[0];
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                if ($data['password'] === $data['repeatPassword'] && strlen($data['password']) >= 6) {
                    // Encode new password.
                    $encoder = $this->container->get('security.password_encoder');
                    $encoded = $encoder->encodePassword($user, $data['password']);
                    $user->setPassword($encoded);

                    // Persist user.
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush($user);

                    // Redirect to login page.
                    return $this->redirectToRoute('login');

                } else {
                    $error = 'error.repeat_password';
                }

            } else {

            }
        } else {
            return $this->render('@App/security/reset_password_error.html.twig', array(
                'error' => 'error.user_token_not_found'
            ));
        }

        return $this->render('@App/security/confirm_reset_password.html.twig', array(
            'form' => $form->createView(),
            'error' => $error
        ));
    }


    /**
     * @Route("/redirect", name="security_redirect")
     */
    public function redirectAction()
    {
        $session = $this->get('session');
        $redirect = $session->get('redirect');

        if ($redirect) {
            $session->remove('redirect');
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('homepage');
    }
}