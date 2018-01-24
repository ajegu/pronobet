<?php


namespace AppBundle\Controller;

use AppBundle\Entity\SportForecast;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\User;
use AppBundle\Form\CommentType;
use ForecastBundle\Service\TipsterService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * Class SportForecastController
 * @package AppBundle\Controller
 * @Route("pronostic")
 */
class SportForecastController extends Controller
{
    private function getSportForecasts(Request $request, $vip = false, User $user = null)
    {
        $sportId = $request->get('sport');
        $tipsterId = $request->get('tipster');

        $tipsterFilter = null;
        $sportFilter = null;

        if ($sportId) {
            $sportFilter = $this->getDoctrine()->getRepository('AppBundle:Sport')
                ->find($sportId);
        }

        if ($tipsterId) {
            $tipsterFilter = $this->getDoctrine()->getRepository('AppBundle:Tipster')
                ->find($tipsterId);
        }

        $sportForecasts = [];
        if ($vip == true) {
            $tipsters = [];
            foreach ($user->getSubscriptions() as $subscription) {
                if ($subscription->getStatus() === SubscriptionStatus::Vip) {
                    $tipsters[] = $subscription->getTipster()->getId();
                }
            }
            $sportForecasts = $this->getDoctrine()->getRepository('AppBundle:SportForecast')
                ->getVIPSportForecasts($sportId, $tipsterId, $tipsters);

        } else {
            $sportForecasts = $this->getDoctrine()->getRepository('AppBundle:SportForecast')
                ->getFreeSportForecasts($sportId, $tipsterId);
        }

        $sports = [];
        $tipsters = [];
        foreach($sportForecasts as $sportForecast) {

            if (!array_key_exists($sportForecast->getTipster()->getId(), $tipsters)) {
                $tipsters[$sportForecast->getTipster()->getId()] = $sportForecast->getTipster();
            }

            foreach ($sportForecast->getSportBets() as $sportBet) {

                if (!array_key_exists($sportBet->getSport()->getId(), $sports)) {
                    $sports[$sportBet->getSport()->getId()] = $sportBet->getSport();
                }
            }
        }

        usort($sports, function($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        usort($tipsters, function($a, $b) {
            return strcmp($a->getUser()->getNickname(), $b->getUser()->getNickname());
        });

        return [
            'sportForecasts' => $sportForecasts,
            'tipsters' => $tipsters,
            'sports' => $sports,
            'sportFilter' => $sportFilter,
            'tipsterFilter' => $tipsterFilter
        ];
    }

    /**
     * @Route("/", name="sport_forecast_index")
     * @Method("GET")
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.free_sport_forecast_list', $this->get("router")->generate("sport_forecast_index"));

        $result = $this->getSportForecasts($request, false);

        return $this->render('AppBundle:SportForecast:index.html.twig', $result);
    }

    /**
     * @Route("/historique/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="sport_forecast_history")
     * @Method("GET")
     */
    public function historyAction(Request $request, $page, $count)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));
        $breadcrumbs->addItem('label.free_sport_forecast_list', $this->get("router")->generate("sport_forecast_index"));
        $breadcrumbs->addItem('label.history', $this->get("router")->generate("sport_forecast_history"));

        $firstResult = ($page * $count) - $count;

        $sportForecasts = $this->getDoctrine()->getRepository('AppBundle:SportForecast')
            ->getSportForecastsHistory($firstResult, $count);

        return $this->render('AppBundle:SportForecast:history.html.twig', [
            'sportForecasts' => $sportForecasts,
            'page' => $page,
            'count' => $count
        ]);
    }

    /**
     * @Route("/vip", name="sport_forecast_vip")
     * @Method("GET")
     * @return Response
     *
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function vipAction(Request $request)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        // check user subscriptions
        $hasSubscription = false;
        foreach ($userLogged->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Vip && $subscription->getFinishedAt() > new \DateTime()) {
                $hasSubscription = true;
                break;
            }
        }

        if ($hasSubscription === false) {
            $breadcrumbs->addItem('label.subscription');
            return $this->render('AppBundle:Tipster:subscription.html.twig');
        }


        $breadcrumbs->addItem('label.vip_sport_forecast_list', $this->get("router")->generate("sport_forecast_vip"));
        $result = $this->getSportForecasts($request, true, $userLogged);

        return $this->render('AppBundle:SportForecast:vip.html.twig', $result);
    }

    /**
     * @Route("/{id}", name="sport_forecast_show")
     * @Method("GET")
     */
    public function showAction(SportForecast $sportForecast)
    {
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem('label.home', $this->get("router")->generate("homepage"));

        if ($sportForecast->getIsVip() && $sportForecast->getIsValidate() === false) {
            // check user login
            if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $this->get('session')->set('redirect', $this->get('router')->generate('sport_forecast_show', ['id' => $sportForecast->getId()]));
                return $this->redirectToRoute('login');
            }
            $this->get('session')->remove('redirect');

            $userLogged = $this->get('security.token_storage')->getToken()->getUser();

            // check user subscriptions
            $hasSubscription = false;
            foreach ($userLogged->getSubscriptions() as $subscription) {
                if ($subscription->getTransactionId() !== null && $subscription->getFinishedAt() > new \DateTime()) {
                    $hasSubscription = true;
                    break;
                }
            }

            if ($hasSubscription === false) {
                $breadcrumbs->addItem('label.subscription');
                return $this->render('AppBundle:Tipster:subscription.html.twig');
            }
        }

        if ($sportForecast->getIsValidate()) {
            $breadcrumbs->addItem('label.free_sport_forecast_list', $this->get("router")->generate("sport_forecast_index"));
            $breadcrumbs->addItem('label.history', $this->get("router")->generate("sport_forecast_history"));
        } else {
            if ($sportForecast->getIsVip()) {
                $breadcrumbs->addItem('label.vip_sport_forecast_list', $this->get("router")->generate("sport_forecast_vip"));
            } else {
                $breadcrumbs->addItem('label.free_sport_forecast_list', $this->get("router")->generate("sport_forecast_index"));
            }
        }
        $breadcrumbs->addItem('label.sport_forecast_details');

        $commentForm = $this->createForm(CommentType::class);

        $tipsterService = new TipsterService(
            $this->getDoctrine()->getManager(),
            $this->get('app.cache.tipster'),
            $sportForecast->getTipster()
        );

        $tipsterStats = $tipsterService->getStatsLast30Days();

        //TODO Check the subscribing

        return $this->render('AppBundle:SportForecast:show.html.twig', [
            'sportForecast' => $sportForecast,
            'tipsterStats' => $tipsterStats,
            'commentForm' => $commentForm->createView()
        ]);
    }
}