<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Sport;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class ChampionshipController
 * @package ApiBundle\Controller
 *
 * @Route("/api/championship")
 */
class ChampionshipController extends Controller
{
    /**
     * @Route("/{id}", name="api_championship_index")
     */
    public function indexAction(Sport $sport)
    {
        $championships = $this->getDoctrine()
            ->getRepository('AppBundle:Championship')
            ->getChampionships($sport->getId(), 0, 0, '', 1, false);


        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(['sport', 'sportBets', 'createdAt', 'updatedAt']);
        $encoder = new JsonEncoder();

        $serializer = new Serializer([$normalizer], [$encoder]);
        return new JsonResponse($serializer->serialize($championships, 'json'));
    }

}
