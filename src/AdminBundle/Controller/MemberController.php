<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\MemberType;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MemberController
 * @package AdminBundle\Controller
 * @Route("admin/member")
 */
class MemberController extends Controller
{

    /**
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/show/{id}", name="admin_member_show")
     */
    public function showAction(User $user)
    {

        if ($user->getRole() == 'ROLE_TIPSTER') {
            return $this->redirectToRoute('admin_tipster_show', ['id' => $user->getTipster()->getId()]);
        }

        return $this->render('AdminBundle:Member:show.html.twig', array(
            'member' => $user
        ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/add", name="admin_member_add")
     */
    public function addAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm('AdminBundle\Form\MemberType', $user, array(
            'translator' => $this->get('translator')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Encode manually the password with bcrypt.
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);

            $user->setRole('ROLE_MEMBER');

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush($user);

            return $this->redirectToRoute('admin_member_show', ['id' => $user->getId()]);

        }

        return $this->render('AdminBundle:Member:add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/edit/{id}", name="admin_member_edit")
     */
    public function editAction(Request $request, User $user)
    {
        $form = $this->createForm('AdminBundle\Form\MemberType', $user, array(
            'translator' => $this->get('translator')
        ));

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $user->setUpdatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush($user);

            return $this->redirectToRoute('admin_member_show', ['id' => $user->getId()]);
        }



        return $this->render('AdminBundle:Member:edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_member_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_member_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm();
        $form->handleRequest($request);

        $error = '';
        $translator = $this->get('translator');

        if (count($user->getSubscriptions()) > 0) {
            $error = $translator->trans('text.user_delete_many_subscriptions');
        }

        if (count($user->getComments()) > 0) {
            $error = $translator->trans('text.user_delete_many_comments');
        }


        if ($error !== '') {
            return $this->render('AdminBundle:Member:delete.html.twig', array(
                'form' => $form->createView(),
                'member' => $user,
                'error' => $error
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush($user);

            $this->addFlash('success', $translator->trans('text.member_delete_success'));

            return $this->redirectToRoute('admin_member_index');
        }

        return $this->render('AdminBundle:Member:delete.html.twig', array(
            'form' => $form->createView(),
            'member' => $user
        ));

    }

    /**
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\Response@
     *
     * @Route("/member-to-tipster/{id}", name="member_to_tipster")
     * @Method({"GET"})
     */
    public function upgradeToTipsterAction(User $user)
    {

        return $this->render('AdminBundle:Member:upgrade_to_tipster.html.twig', array(
            'member' => $user
        ));
    }

    /**
     * @Route("/{page}-{count}", defaults={"page" = 1, "count" = 5}, name="admin_member_index")
     * @Method({"GET"})
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
        $paginator = $em->getRepository('AppBundle:User')->getMembers($firstResult, $count, $search, $status);

        return $this->render('AdminBundle:Member:index.html.twig', array(
            'members' => $paginator,
            'page' => $page,
            'count' => $count
        ));
    }

}
