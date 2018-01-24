<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\BookmakerType;
use AppBundle\Entity\Bookmaker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BookmakerController
 * @package AdminBundle\Controller
 *
 * @Route("/admin/bookmaker")
 */
class BookmakerController extends Controller
{
    /**
     * @Route("/show/{id}", name="admin_bookmaker_show")
     * @Method("GET")
     */
    public function showAction(Bookmaker $bookmaker)
    {
        return $this->render('AdminBundle:Bookmaker:show.html.twig', array(
            'bookmaker' => $bookmaker
        ));
    }

    /**
     * @Route("/add", name="admin_bookmaker_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $bookmaker = new Bookmaker();
        $bookmaker->setVisible(true);
        $form = $this->createForm(BookmakerType::class, $bookmaker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($bookmaker);
            $em->flush();

            return $this->redirectToRoute('admin_bookmaker_show', array(
                'id' => $bookmaker->getId()
            ));
        }

        return $this->render('AdminBundle:Bookmaker:add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_bookmaker_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Bookmaker $bookmaker)
    {
        $form = $this->createForm(BookmakerType::class, $bookmaker);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $bookmaker->setUpdatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($bookmaker);
            $em->flush($bookmaker);

            return $this->redirectToRoute('admin_bookmaker_show', ['id' => $bookmaker->getId()]);

        }

        return $this->render('AdminBundle:Bookmaker:edit.html.twig', array(
            'form' => $form->createView(),
            'bookmaker' => $bookmaker
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_bookmaker_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Bookmaker $bookmaker)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_bookmaker_delete', array('id' => $bookmaker->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';

        if (count($bookmaker->getSportForecasts()) > 0) {
            $error = $this->get('translator')->trans('text.bookmaker_delete_many_sport_forecast');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:Bookmaker:delete.html.twig', array(
                'form' => $form->createView(),
                'bookmaker' => $bookmaker,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($bookmaker);
            $em->flush($bookmaker);

            $this->addFlash('success', $this->get('translator')->trans('text.bookmaker_delete_success'));

            return $this->redirectToRoute('admin_bookmaker_index');
        }

        return $this->render('AdminBundle:Bookmaker:delete.html.twig', array(
            'form' => $form->createView(),
            'bookmaker' => $bookmaker
        ));
    }

    /**
     * @Route("/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="admin_bookmaker_index")
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
        $paginator = $em->getRepository('AppBundle:Bookmaker')->getBookmakers($firstResult, $count, $search, $state);

        return $this->render('AdminBundle:Bookmaker:index.html.twig', array(
            'bookmakers' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

}
