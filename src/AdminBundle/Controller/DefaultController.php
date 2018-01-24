<?php

namespace AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 * Class DefaultController
 * @package AdminBundle\Controller
 * @Route("admin")
 */
class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="admin_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        return $this->render('@Admin/Default/index.html.twig');
    }
}
