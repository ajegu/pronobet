<?php

namespace ForecastBundle\Controller;

use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\User;
use ForecastBundle\Form\SportBetType;
use ForecastBundle\Form\SportForecastType;
use ForecastBundle\Form\MessageType;
use ForecastBundle\Form\TicketType;
use ForecastBundle\Service\TipsterService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class SportForecastController
 * @package ForecastBundle\Controller
 *
 * @Route("/forecast/sport-forecast")
 */
class SportForecastController extends Controller implements NotificationController
{
    private function checkSportForecastTipster(SportForecast $sportForecast, User $user)
    {
        if ($sportForecast->getTipster()->getId() !== $user->getTipster()->getId()) {
            throw new AccessDeniedHttpException("sport forecast owner is not you");
        }
    }

    private function checkSportForecastEdit(SportForecast $sportForecast)
    {
        if ($sportForecast->isEditable() === false) {
            throw new UnauthorizedHttpException("sport forecast editable", "sport forecast is not editable");
        }
    }

    private function checkSportForecastValid(SportForecast $sportForecast)
    {
        if ($sportForecast->isValid() === false) {
            throw new UnauthorizedHttpException("sport forecast invalid", "sport forecast is not valid");
        }
    }

    /**
     * @Route("/unpublished/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="forecast_sport_forecast_unpublished")
     * @Method({"GET"})
     */
    public function unpublishedAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $isVip = (string) $request->get('is-vip');
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($search !== '' || $isVip !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:SportForecast')
            ->getSportForecastsUnpublished($firstResult, $count, $search, $isVip, $userLogged->getTipster());

        return $this->render('ForecastBundle:SportForecast:unpublished.html.twig', array(
            'sportForecasts' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }


    /**
     * @Route("/in-progress/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="forecast_sport_forecast_in_progress")
     * @Method({"GET"})
     */
    public function inProgressAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $isVip = (string) $request->get('is-vip');
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($search !== '' || $isVip !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:SportForecast')
            ->getSportForecastsInProgress($firstResult, $count, $search, $isVip, $userLogged->getTipster());

        return $this->render('@Forecast/SportForecast/in_progress.html.twig', array(
            'sportForecasts' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

    /**
     * @Route("/to-validate/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="forecast_sport_forecast_to_validate")
     * @Method({"GET"})
     */
    public function toValidateAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $isVip = (string) $request->get('is-vip');
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($search !== '' || $isVip !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:SportForecast')
            ->getSportForecastsToValidate($firstResult, $count, $search, $isVip, $userLogged->getTipster());

        return $this->render('@Forecast/SportForecast/to_validate.html.twig', array(
            'sportForecasts' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

    /**
     * @Route("/history/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="forecast_sport_forecast_history")
     * @Method({"GET"})
     */
    public function historyAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $isVip = (string) $request->get('is-vip');
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        if ($search !== '' || $isVip !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:SportForecast')
            ->getSportForecastsHistoryByTipster($firstResult, $count, $search, $isVip, $userLogged->getTipster());

        return $this->render('@Forecast/SportForecast/history.html.twig', array(
            'sportForecasts' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

    /**
     * @Route("/show/{id}", name="forecast_sport_forecast_show")
     * @Method("GET")
     */
    public function showAction(SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());

        $sportId = 0;
        $sportBet = new SportBet($sportForecast);
        $sports = $this->getDoctrine()->getRepository('AppBundle:Sport')->getSports(0, 0, '', 1);

        $form = $this->createForm(SportBetType::class, $sportBet, ['sports' => $sports, 'sportId' => $sportId]);

        return $this->render('ForecastBundle:SportForecast:show.html.twig', array(
            'sportForecast' => $sportForecast,
            'form' => $form->createView(),
            'sportBet' => $sportBet
        ));
    }

    /**
     * @Route("/add", name="forecast_sport_forecast_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $userLogged = $this->get('security.token_storage')->getToken()->getUser();
        $sportForecast = new SportForecast($userLogged->getTipster());

        $form = $this->createForm(SportForecastType::class, $sportForecast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($sportForecast);
            $em->flush($sportForecast);

            $this->get('session')->remove('sport_forecast_unpublished');

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportForecast:form.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/edit/{id}", name="forecast_sport_forecast_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastEdit($sportForecast);

        $form = $this->createForm(SportForecastType::class, $sportForecast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $sportForecast->setUpdatedAt(new \DateTime());

            $em->persist($sportForecast);
            $em->flush($sportForecast);

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportForecast:form.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/delete/{id}", name="forecast_sport_forecast_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastEdit($sportForecast);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_forecast_delete', array('id' => $sportForecast->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sportForecast);
            $em->flush($sportForecast);

            $this->get('session')->remove('sport_forecast_unpublished');

            return $this->redirectToRoute('forecast_sport_forecast_unpublished');
        }

        return $this->render('ForecastBundle:SportForecast:delete.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/publish/{id}", name="forecast_sport_forecast_publish")
     * @Method({"GET", "POST"})
     */
    public function publishAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastEdit($sportForecast);

        if ($sportForecast->isPublishable() !== true) {
            return $this->render('ForecastBundle:SportForecast:publish.html.twig', [
                'error' => $this->get('translator')->trans('error.sport_forecast_publish'),
                'sportForecast' => $sportForecast
            ]);
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_forecast_publish', ['id' => $sportForecast->getId()]))
            ->setMethod('POST')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $sportForecast->setPublishedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $em->persist($sportForecast);
            $em->flush($sportForecast);

            $this->get('session')->remove('sport_forecast_unpublished');

            // send notification messages
            $awsService = $this->get('aws');

            $emailQueue = $this->getParameter('aws_sqs.notification_email');
            $awsService->sendMessage($sportForecast->getId(), $emailQueue);

            $smsQueue = $this->getParameter('aws_sqs.notification_sms');
            $awsService->sendMessage($sportForecast->getId(), $smsQueue);

            return $this->redirectToRoute('forecast_sport_forecast_in_progress');
        }

        return $this->render('ForecastBundle:SportForecast:publish.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/submit-change/{id}", name="forecast_sport_forecast_submit_change")
     * @Method({"GET", "POST"})
     */
    public function submitChangeAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());

        $form = $this->createForm(MessageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $admins = $this->getDoctrine()->getRepository('AppBundle:User')
                ->findByRole('ROLE_ADMIN');

            $data = $form->getData();

            foreach ($admins as $admin) {
                $subject = sprintf("%s - %s #%s",
                    $this->container->getParameter('app_name'),
                    $this->get('translator')->trans('title.sport_forecast_need_change'),
                    $sportForecast->getId());
                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($this->container->getParameter('mailer_sender_address'))
                    ->setTo($admin->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@Forecast/Emails/submit_change.html.twig',
                            array(
                                'sportForecast' => $sportForecast,
                                'message' => $data['message']
                            )
                        ),
                        'text/html'
                    );
                $code = $this->get('mailer')->send($message);
            }

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportForecast:submit_change.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/edit-ticket/{id}", name="forecast_sport_forecast_edit_ticket")
     * @Method({"GET", "POST"})
     */
    public function editTicketAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());

        $form = $this->createForm(TicketType::class, $sportForecast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $sportForecast->setUpdatedAt(new \DateTime());

            $em->persist($sportForecast);
            $em->flush($sportForecast);

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportForecast:edit_ticket.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));
    }

    /**
     * @Route("/validate/{id}", name="forecast_sport_forecast_validate")
     * @Method({"GET", "POST"})
     */
    public function validateAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastValid($sportForecast);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_forecast_validate', array('id' => $sportForecast->getId())))
            ->setMethod('POST')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            foreach ($sportForecast->getSportBets() as $sportBet) {
                $result = (int) $request->get('result_' . $sportBet->getId());

                if ($result === 2) {
                    $sportBet->setCancelled(true);
                } else if ($result === 1) {
                    $sportBet->setIsWon(true);
                } else {
                    $sportBet->setIsWon(false);
                }
            }

            return $this->render('ForecastBundle:SportForecast:validate_confirm.html.twig', array(
                'sportForecast' => $sportForecast,
                'form' => $form->createView()
            ));
        }

        return $this->render('ForecastBundle:SportForecast:validate.html.twig', array(
            'sportForecast' => $sportForecast,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/validate-confirm/{id}", name="forecast_sport_forecast_validate_confirm")
     * @Method({"GET", "POST"})
     */
    public function validateConfirmAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastValid($sportForecast);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_forecast_validate_confirm', array('id' => $sportForecast->getId())))
            ->setMethod('POST')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($sportForecast->getSportBets() as $sportBet) {

                $result = (int) $request->get('result_' . $sportBet->getId());


                if ($result === 2) {
                    $sportBet->setCancelled(true);
                } else if ($result === 1) {
                    $sportBet->setIsWon(true);
                } else {
                    $sportBet->setIsWon(false);
                }

                $sportBet->setUpdatedAt(new \DateTime());
                $em->persist($sportBet);
            }

            $sportForecast->setIsValidate(true)
                ->setUpdatedAt(new \DateTime());
            $em->persist($sportForecast);

            $tipster = $sportForecast->getTipster();
            $tipster->setUpdatedAt(new \DateTime());
            $em->persist($tipster);

            $em->flush();

            // invalid tipster stats cache
            $this->get('app.cache.tipster')->deleteItem('tipster.stats' . $tipster->getId());

            $cnt = $this->getDoctrine()->getRepository('AppBundle:SportForecast')
                ->getSportForecastsToValidate(0, 0, '', '', $tipster)->count();

            if ($cnt > 0) {
                return $this->redirectToRoute('forecast_sport_forecast_to_validate');
            }

            return $this->redirectToRoute('forecast_index');

        }

        return $this->render('ForecastBundle:SportForecast:validate_confirm.html.twig', array(
            'sportForecast' => $sportForecast,
            'form' => $form->createView()
        ));
    }

    /**
     * @param Request $request
     * @param SportForecast $sportForecast
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/cancellation/{id}", name="forecast_sport_forecast_cancellation")
     * @Method({"GET", "POST"})
     */
    public function cancellationAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_forecast_cancellation', array('id' => $sportForecast->getId())))
            ->setMethod('POST')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            foreach ($sportForecast->getSportBets() as $sportBet) {
                $sportBet->setCancelled(true)
                    ->setUpdatedAt(new \DateTime());

                $em->persist($sportBet);
                $em->flush();
            }

            $sportForecast->setIsValidate(true)
                ->setUpdatedAt(new \DateTime());
            $em->persist($sportForecast);
            $em->flush();


            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportForecast:cancellation.html.twig', array(
            'sportForecast' => $sportForecast,
            'form' => $form->createView()
        ));
    }

}
