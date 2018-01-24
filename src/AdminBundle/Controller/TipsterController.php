<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\TipsterType;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionStatus;
use AppBundle\Entity\Tipster;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TipsterController
 * @package AdminBundle\Controller
 *
 * @Route("/admin/tipster")
 */
class TipsterController extends Controller
{
    /**
     * @Route("/add/{id}", name="admin_tipster_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request, User $user)
    {
        $tipster = new Tipster($user);

        $form = $this->createForm(TipsterType::class, $tipster);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setRole('ROLE_TIPSTER');

            $em = $this->getDoctrine()->getManager();
            $em->persist($tipster);
            $em->persist($user);

            $subscription = new Subscription($tipster, $user);
            $finishedAt = new \DateTime();
            $finishedAt->add(new \DateInterval("P100Y"));
            $subscription->setFinishedAt($finishedAt)
                ->setSmsNotification(false)
                ->setEmailNotification(false)
                ->setAmount(0)
                ->setFees(0)
                ->setActivate(true)
                ->setStatus(SubscriptionStatus::Vip);

            $em->persist($subscription);

            $em->flush();

            return $this->redirectToRoute('admin_tipster_show', array(
                'id' => $tipster->getId()
            ));
        }

        return $this->render('AdminBundle:Tipster:add.html.twig', array(
            'form' => $form->createView(),
            'user' => $user
        ));
    }

    /**
     * @Route("/show/{id}", name="admin_tipster_show")
     * @Method("GET")
     */
    public function showAction(Tipster $tipster)
    {
        return $this->render('AdminBundle:Tipster:show.html.twig', array(
            'tipster' => $tipster
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_tipster_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Tipster $tipster)
    {
        $form = $this->createForm(TipsterType::class, $tipster);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $tipster->setUpdatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($tipster);
            $em->flush();

            return $this->redirectToRoute('admin_tipster_show', array(
                'id' => $tipster->getId()
            ));
        }

        return $this->render('AdminBundle:Tipster:edit.html.twig', array(
            'tipster' => $tipster,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_tipster_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Tipster $tipster)
    {
        $translator = $this->get('translator');

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_tipster_delete', array('id' => $tipster->getId())))
            ->setMethod('DELETE')
            ->getForm();

        $form->handleRequest($request);

        $error = '';

        if (count($tipster->getSportForecasts()) > 0) {
            $error = $translator->trans('text.tipster_delete_many_sport_forecast');
        }

        if (count($tipster->getSubscriptions()) > 1) {
            $error = $translator->trans('text.tipster_delete_many_subscription');
        }

        if ($error !== '') {
            return $this->render('AdminBundle:Tipster:delete.html.twig', array(
                'form' => $form->createView(),
                'tipster' => $tipster,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $user = $tipster->getUser();
            $user->setRole('ROLE_MEMBER');
            $user->setUpdatedAt(new \DateTime());
            $em->persist($user);
            $em->flush($user);

            foreach ($tipster->getSubscriptions() as $subscription) {
                $em->remove($subscription);
            }

            $em->remove($tipster);

            $em->flush();

            $this->addFlash('success', $translator->trans('text.tipster_delete_success'));

            return $this->redirectToRoute('admin_tipster_index');
        }

        return $this->render('AdminBundle:Tipster:delete.html.twig', array(
            'form' => $form->createView(),
            'tipster' => $tipster
        ));
    }


    /**
     * @Route("/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="admin_tipster_index")
     */
    public function indexAction(Request $request, $page, $count)
    {
        $search = (string) $request->get('search');
        $status = (string) $request->get('status');

        if ($search !== '' || $status !== '') {
            $page = 1;
        }

        $em = $this->getDoctrine()->getManager();
        $firstResult = ($page * $count) - $count;

        $paginator = $em->getRepository('AppBundle:User')->getTipsters($firstResult, $count, $search, $status);

        return $this->render('AdminBundle:Tipster:index.html.twig', array(
            'users' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

}
