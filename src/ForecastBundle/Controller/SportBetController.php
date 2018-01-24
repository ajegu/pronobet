<?php

namespace ForecastBundle\Controller;

use AppBundle\Entity\SportBet;
use AppBundle\Entity\SportForecast;
use AppBundle\Entity\User;
use ForecastBundle\Form\SportBetType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class SportBetController
 * @package ForecastBundle\Controller
 *
 * @Route("/forecast/sport-bet")
 */
class SportBetController extends Controller
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
            throw new UnauthorizedHttpException("sport forecast invalid", "sport forecast is not editable");
        }
    }

    private function checkSportBetEdit(SportBet $sportBet)
    {
        if ($sportBet->isEditable() === false) {
            throw new UnauthorizedHttpException("sport bet invalid", "sport bet is not editable");
        }
    }

    /**
     * @Route("/add/{id}", name="forecast_sport_bet_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request, SportForecast $sportForecast)
    {
        $this->checkSportForecastTipster($sportForecast, $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportForecastEdit($sportForecast);

        $sportBet = new SportBet($sportForecast);

        $sportId = false;
        $data = $request->request->get('forecastbundle_sportbet');
        if (count($data) > 0) {
            $sportId = $data['sport'];
        }

        $sports = $this->getDoctrine()->getRepository('AppBundle:Sport')->getSports(0, 0, '', 1);

        $form = $this->createForm(SportBetType::class, $sportBet, ['sports' => $sports, 'sportId' => $sportId]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $em->persist($sportBet);
            $em->flush($sportBet);

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportForecast->getId()]);
        }

        return $this->render('ForecastBundle:SportBet:form.html.twig', array(
            'form' => $form->createView(),
            'sportForecast' => $sportForecast,
            'sportBet' => $sportBet
        ));
    }

    /**
     * @Route("/edit/{id}", name="forecast_sport_bet_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SportBet $sportBet)
    {
        $this->checkSportForecastTipster($sportBet->getSportForecast(), $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportBetEdit($sportBet);

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
            $em->flush($sportBet);

            return $this->redirectToRoute('forecast_sport_forecast_show', ['id' => $sportBet->getSportForecast()->getId()]);
        }

        return $this->render('ForecastBundle:SportBet:form.html.twig', array(
            'form' => $form->createView(),
            'sportBet' => $sportBet
        ));
    }

    /**
     * @Route("/delete/{id}", name="forecast_sport_bet_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, SportBet $sportBet)
    {
        $this->checkSportForecastTipster($sportBet->getSportForecast(), $this->get('security.token_storage')->getToken()->getUser());
        $this->checkSportBetEdit($sportBet);

        $sportForecastId = $sportBet->getSportForecast()->getId();

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('forecast_sport_bet_delete', array('id' => $sportBet->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sportBet);
            $em->flush($sportBet);

            return $this->redirectToRoute('forecast_sport_forecast_show', [
                'id' => $sportForecastId
            ]);
        }


        return $this->render('ForecastBundle:SportBet:delete.html.twig', array(
            'form' => $form->createView(),
            'sportBet' => $sportBet,
            'sportForecastId' => $sportForecastId
        ));
    }
}
