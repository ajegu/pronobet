<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\SportForecast;
use AppBundle\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CommentController
 * @package AppBundle\Controller
 *
 * @Route("/comment")
 */
class CommentController extends Controller
{
    /**
     * @Route("/add/{id}", name="comment_add")
     */
    public function addAction(Request $request, SportForecast $sportForecast)
    {
        $session = $this->get('session');

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $session->set('comment', $request->request->get('appbundle_comment')['text']);
            $session->set('redirect', $this->get('router')->generate('comment_add', ['id' => $sportForecast->getId()]));
            return $this->redirectToRoute('login');
        }

        $userLogged = $this->get('security.token_storage')->getToken()->getUser();

        $comment = new Comment($userLogged, $sportForecast);

        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($session->get('comment')) {
            $comment->setText($session->get('comment'));
            $em->persist($comment);
            $em->flush();
            $session->remove('comment');
            $session->remove('redirect');

        } else if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $em->persist($comment);
            $em->flush();
        }

        return $this->redirectToRoute('sport_forecast_show', ['id' => $sportForecast->getId()]);
    }
}
