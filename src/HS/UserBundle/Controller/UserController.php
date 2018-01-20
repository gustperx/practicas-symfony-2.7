<?php

namespace HS\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('HSUserBundle:User');

        $res = 'List Users: <br>';

        foreach ($users->findAll() as $user) {

            $res .= 'User: ' . $user->getUsername() . '<br>';
        }

        return new Response($res);
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('HSUserBundle:User');

        $user = $repository->find($id);

        return new Response($user->getUsername());
    }

}
