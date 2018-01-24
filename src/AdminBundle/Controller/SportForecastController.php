<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\Tipster;
use ForecastBundle\Form\SportBetType;
use ForecastBundle\Form\SportForecastType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SportForecastController
 * @package AdminBundle\Controller
 *
 * @Route("admin/sport-forecast/")
 */
class SportForecastController extends Controller
{

    /**
     * @param Request $request
     * @param SportForecast $sportForecast
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("restore-unpublished/{id}", name="admin_sport_forecast_restore_unpublished")
     *
     */
    public function restoreUnpublishedAction(Request $request, SportForecast $sportForecast)
    {

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_sport_forecast_restore_unpublished', ['id' => $sportForecast->getId()]))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';

        if ($sportForecast->getIsValidate()) {
            $error = $this->get('translator')->trans('text.restore_unpublished_sport_forecast_validate');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:SportForecast:restore_unpublished.html.twig', array(
                'form' => $form->createView(),
                'sportForecast' => $sportForecast,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $sportForecast->setPublishedAt(null)
                ->setUpdatedAt(new \DateTime());

            $em->persist($sportForecast);
            $em->flush();

            $this->addFlash('success', $this->get('translator')->trans('text.sport_forecast_restore_unpublished_success'));

            return $this->redirectToRoute('admin_sport_forecast_index');
        }

        return $this->render('AdminBundle:SportForecast:restore_unpublished.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));

    }

    /**
     * @param Request $request
     * @param SportForecast $sportForecast
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("restore-to-validate/{id}", name="admin_sport_forecast_restore_to_validate")
     */
    public function restoreToValidateAction(Request $request, SportForecast $sportForecast)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_sport_forecast_restore_to_validate', ['id' => $sportForecast->getId()]))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';

        if ($sportForecast->getPublishedAt() === null) {
            $error = $this->get('translator')->trans('error.restore_unpublished_sport_forecast_unpublished');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:SportForecast:restore_to_validate.html.twig', array(
                'form' => $form->createView(),
                'sportForecast' => $sportForecast,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($sportForecast);

            $sportForecast->setIsValidate(false)
                ->setUpdatedAt(new \DateTime());
            $em->persist($sportForecast);

            foreach ($sportForecast->getSportBets() as $sportBet) {
                $sportBet->setIsWon(false)
                    ->setCancelled(false)
                    ->setUpdatedAt(new \DateTime());
                $em->persist($sportBet);
            }

            $em->flush();

            // invalid tipster stats cache
            $this->get('app.cache.tipster')->deleteItem('tipster.stats' . $sportForecast->getTipster()->getId());

            $em->persist($sportForecast);
            $em->flush();

            $this->addFlash('success', $this->get('translator')->trans('text.sport_forecast_restore_to_validate_success'));

            return $this->redirectToRoute('admin_sport_forecast_index');
        }

        return $this->render('AdminBundle:SportForecast:restore_to_validate.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));

    }

    /**
     * @Route("show-sport-forecast/{id}", name="admin_sport_forecast_show_sport_forecast")
     */
    public function showSportForecastAction(SportForecast $sportForecast)
    {
        return $this->render('AdminBundle:SportForecast:show.html.twig', array(
            'sportForecast' => $sportForecast
        ));
    }


    /**
     * @param Request $request
     * @param SportForecast $sportForecast
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("edit-sport-forecast/{id}", name="admin_sport_forecast_edit_sport_forecast")
     */
    public function editSportForecastAction(Request $request, SportForecast $sportForecast)
    {
        $form = $this->createForm(SportForecastType::class, $sportForecast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $sportForecast->setUpdatedAt(new \DateTime());

            $em->persist($sportForecast);
            $em->flush($sportForecast);

            return $this->redirectToRoute('admin_sport_forecast_show_sport_forecast', ['id' => $sportForecast->getId()]);
        }

        return $this->render('AdminBundle:SportForecast:edit_sport_forecast.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast
        ));

    }

    /**
     * @Route("edit-sport-bet/{id}", name="admin_sport_forecast_edit_sport_bet")
     */
    public function editSportBetAction(Request $request, SportBet $sportBet)
    {
        $sportId = $sportBet->getSport()->getId();
        $data = $request->request->get('forecastbundle_sportbet');
        if (count($data) > 0) {
            $sportId = $data['sport'];
        }

        $sports = $this->getDoctrine()->getRepository('AppBundle:Sport')->getSports(0, 0, '', 1);

        $form = $this->createForm(SportBetType::class, $sportBet, ['sports' => $sports, 'sportId' => $sportId]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $sportBet->setUpdatedAt(new \DateTime());

            $em->persist($sportBet);
            $em->flush();

            return $this->redirectToRoute('admin_sport_forecast_show_sport_forecast', ['id' => $sportBet->getSportForecast()->getId()]);
        }

        return $this->render('AdminBundle:SportForecast:edit_sport_bet.html.twig', array(
            'form' => $form->createView(),
            'sportBet' => $sportBet
        ));

    }

    /**
     * @Route("/{page}-{count}+{id}", defaults={"page" = 1, "count" = 5, "id" = 0}, name="admin_sport_forecast_index")
     */
    public function indexAction(Request $request, $page, $count, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $tipster = $id;
        if ($tipster === 0) {
            $tipster = (int) $request->get('tipster');
        }

        $state = (string) $request->get('state');
        $vip = (string) $request->get('vip');

        if ($state !== '' || $vip !== '') {
            $page = 1;
        }

        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:SportForecast')->getAllPublishedSportForecasts($firstResult, $count, $state, $vip, $tipster);

        $tipsters = $em->getRepository('AppBundle:Tipster')
            ->findAll();

        return $this->render('AdminBundle:SportForecast:index.html.twig', array(
            'sportForecasts' => $paginator,
            'tipsters' => $tipsters,
            'page' => $page,
            'count' => $count,
            'tipsterId' => $tipster
        ));
    }

}
