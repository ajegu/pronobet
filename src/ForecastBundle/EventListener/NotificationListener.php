<?php


namespace ForecastBundle\EventListener;


use Doctrine\ORM\EntityManager;
use ForecastBundle\Controller\NotificationController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class NotificationListener
{
    private $em;
    private $session;
    private $tokenStorage;

    public function __construct(EntityManager $em, Session $session, TokenStorage $tokenStorage)
    {
        $this->em = $em;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }


        if ($controller[0] instanceof NotificationController) {

            $user = $this->tokenStorage->getToken()->getUser();
            $tipster = $user->getTipster();

            $cnt = $this->session->get('sport_forecast_unpublished');

            if ($cnt === null) {
                $sportForecastsUnpublished = $this->em->getRepository('AppBundle:SportForecast')
                    ->getSportForecastsUnpublished(0, 0, '', '', $tipster);

                $this->session->set('sport_forecast_unpublished', $sportForecastsUnpublished->count());
            }

            $sportForecastsInProgress = $this->em->getRepository('AppBundle:SportForecast')
                ->getSportForecastsInProgress(0, 0, '', '', $tipster);

            $this->session->set('sport_forecast_in_progress', $sportForecastsInProgress->count());

            $sportForecastsToValidate = $this->em->getRepository('AppBundle:SportForecast')
                ->getSportForecastsToValidate(0, 0, '', '', $tipster);

            $this->session->set('sport_forecast_to_validate', $sportForecastsToValidate->count());
        }
    }
}