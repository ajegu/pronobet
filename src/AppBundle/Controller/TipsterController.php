<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use ForecastBundle\Service\TipsterService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class TipsterController
 * @package AppBundle\Controller
 * @Route("tipster")
 */
class TipsterController extends Controller
{
    /**
     * @Route("/", name="tipster_index")
     */
    public function indexAction()
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.tipster_list', $this->get("router")->generate("tipster_index"));

        $em = $this->getDoctrine()->getManager();
        $ra = $this->get('app.cache.tipster');
        $tipsters = $this->getDoctrine()->getRepository('AppBundle:Tipster')->findAll();

        $tipsterResults = [];
        foreach ($tipsters as $tipster) {
            $tipsterService = new TipsterService($em, $ra, $tipster);
            $stats = $tipsterService->getStatsLast30Days();
            $tipster->setStats($stats);
            $tipsterResults[] = $tipster;
        }

        usort($tipsterResults, function ($a, $b) {
            $statsA = $a->getStats();
            $statsB = $b->getStats();

            return $statsA['sportForecastStats']['all']['roi'] < $statsB['sportForecastStats']['all']['roi'];
        });
        $keys = array_keys($tipsterResults);
        $topTipster = $tipsterResults[$keys[0]];
        unset($tipsterResults[$keys[0]]);

        return $this->render('AppBundle:Tipster:index.html.twig', array(
            'topTipster' => $topTipster,
            'tipsters' => $tipsterResults
        ));
    }

    /**
     * @Route("/show/{id}", name="tipster_show")
     */
    public function showAction(Request $request, Tipster $tipster)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.tipster_list', $this->get("router")->generate("tipster_index"));
        $breadcrumbs->addItem('label.tipster_show');

        $tipsterService = new TipsterService(
            $this->getDoctrine()->getManager(),
            $this->get('app.cache.tipster'),
            $tipster
        );

        return $this->render('AppBundle:Tipster:show.html.twig', array(
            'tipster' => $tipster,
            'stats' => $tipsterService->getStatsLast30Days()
        ));
    }

}
