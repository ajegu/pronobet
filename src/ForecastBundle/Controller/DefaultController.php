<?php

namespace ForecastBundle\Controller;

use ForecastBundle\Service\TipsterService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * @package ForecastBundle\Controller
 *
 * @Route("/forecast")
 */
class DefaultController extends Controller implements NotificationController
{
    /**
     * @Route("/", name="forecast_index")
     */
    public function indexAction(Request $request)
    {
        $startDate = new \DateTime();
        $dt = new \DateInterval('P30D');
        $startDate->sub($dt);
        if ($request->get('start_date')) {
            $startDate = \DateTime::createFromFormat('d/m/Y', $request->get('start_date'));
        }

        $endDate = new \DateTime();
        if($request->get('end_date')) {
            $endDate = \DateTime::createFromFormat('d/m/Y', $request->get('end_date'));
        }

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();


        $tipsterService = new TipsterService(
            $this->getDoctrine()->getManager(),
            $this->get('app.cache.tipster'),
            $userLogged->getTipster()
        );

        return $this->render('ForecastBundle:Default:index.html.twig', [
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'stats' => $tipsterService->calculateStats($startDate, $endDate)
        ]);
    }
}
