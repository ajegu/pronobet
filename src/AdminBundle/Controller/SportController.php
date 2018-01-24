<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\SportType;
use AppBundle\Entity\Sport;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SportController
 * @package AdminBundle\Controller
 *
 * @Route("/admin/sport")
 */
class SportController extends Controller
{

    /**
     * @param Sport $sport
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/show/{id}", name="admin_sport_show")
     * @Method("GET")
     */
    public function showAction(Sport $sport)
    {
        return $this->render('AdminBundle:Sport:show.html.twig', array(
            'sport' => $sport
        ));
    }

    /**
     * @Route("/add", name="admin_sport_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $sport = new Sport();
        $sport->setVisible(true);

        $form = $this->createForm(SportType::class, $sport);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($sport);
            $em->flush($sport);

            return $this->redirectToRoute('admin_sport_show', ['id' => $sport->getId()]);
        }

        return $this->render('AdminBundle:Sport:add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_sport_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Sport $sport)
    {
        $form = $this->createForm(SportType::class, $sport);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $sport->setUpdatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($sport);
            $em->flush($sport);

            return $this->redirectToRoute('admin_sport_show', ['id' => $sport->getId()]);

        }

        return $this->render('AdminBundle:Sport:edit.html.twig', array(
            'form' => $form->createView(),
            'sport' => $sport
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_sport_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Sport $sport)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_sport_delete', array('id' => $sport->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';

        if (count($sport->getChampionships()) > 0) {
            $error = $this->get('translator')->trans('text.sport_delete_many_championships');
        }

        if (count($sport->getSportBets()) > 0) {
            $error = $this->get('translator')->trans('text.sport_delete_many_sport_bets');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:Sport:delete.html.twig', array(
                'form' => $form->createView(),
                'sport' => $sport,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sport);
            $em->flush($sport);

            $this->addFlash('success', $this->get('translator')->trans('text.sport_delete_success'));

            return $this->redirectToRoute('admin_sport_index');
        }

        return $this->render('AdminBundle:Sport:delete.html.twig', array(
            'form' => $form->createView(),
            'sport' => $sport
        ));
    }

    /**
     * @Route("/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="admin_sport_index")
     * @Method({"GET"})
     */
    public function indexAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $state = (string) $request->get('state');

        if ($search !== '' || $state !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;
        $paginator = $em->getRepository('AppBundle:Sport')->getSports($firstResult, $count, $search, $state);

        return $this->render('AdminBundle:Sport:index.html.twig', array(
            'sports' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }
}
