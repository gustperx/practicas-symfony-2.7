<?php

namespace HS\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('HSUserBundle:Default:index.html.twig', array('name' => $name));
    }
}
