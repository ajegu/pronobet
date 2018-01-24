<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\ChampionshipType;
use AppBundle\Entity\Championship;
use AppBundle\Entity\Sport;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ChampionshipController
 * @package AdminBundle\Controller
 *
 * @Route("/admin/championship")
 */
class ChampionshipController extends Controller
{
    /**
     * @Route("/show/{id}", name="admin_championship_show")
     * @Method("GET")
     */
    public function showAction(Championship $championship)
    {
        return $this->render('AdminBundle:Championship:show.html.twig', array(
            'championship' => $championship
        ));
    }

    /**
     * @Route("/add/{id}", name="admin_championship_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request, Sport $sport)
    {
        $championship = new Championship($sport);
        $championship->setVisible(true);

        $form = $this->createForm(ChampionshipType::class, $championship);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($championship);
            $em->flush($championship);

            return $this->redirectToRoute('admin_championship_show', ['id' => $championship->getId()]);
        }

        return $this->render('AdminBundle:Championship:add.html.twig', array(
            'form' => $form->createView(),
            'sport' => $sport
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_championship_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Championship $championship)
    {
        $form = $this->createForm(ChampionshipType::class, $championship);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $championship->setUpdatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($championship);
            $em->flush($championship);

            return $this->redirectToRoute('admin_championship_show', ['id' => $championship->getId()]);
        }

        return $this->render('AdminBundle:Championship:edit.html.twig', array(
            'form' => $form->createView(),
            'championship' => $championship
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_championship_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Championship $championship)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_championship_delete', array('id' => $championship->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';

        if (count($championship->getSportBets()) > 0) {
            $error = $this->get('translator')->trans('text.championship_delete_many_sport_bet');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:Championship:delete.html.twig', array(
                'form' => $form->createView(),
                'championship' => $championship,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $sportId = $championship->getSport()->getId();

            $em = $this->getDoctrine()->getManager();
            $em->remove($championship);
            $em->flush($championship);

            $this->addFlash('success', $this->get('translator')->trans('text.championship_delete_success'));

            return $this->redirectToRoute('admin_championship_index', ['id' => $sportId]);
        }

        return $this->render('AdminBundle:Championship:delete.html.twig', array(
            'form' => $form->createView(),
            'championship' => $championship
        ));
    }

    /**
     * @Route("/{id}-{page}-{count}", defaults={"page" = 1, "count" = 5}, name="admin_championship_index")
     * @Method({"GET"})
     */
    public function indexAction(Request $request, Sport $sport, $page, $count)
    {
        $search = (string) $request->get('search');
        $state = (string) $request->get('state');

        if ($search !== '' || $state !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:Championship')
            ->getChampionships($sport->getId(), $firstResult, $count, $search, $state);

        return $this->render('AdminBundle:Championship:index.html.twig', array(
            'championships' => $paginator,
            'sport' => $sport,
            'page' => $page,
            'count' => $count
        ));
    }

}
