<?php

namespace HS\UserBundle\Controller;

use HS\UserBundle\Entity\User;
use HS\UserBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //$users = $em->getRepository('HSUserBundle:User');

        //$users = $users->findAll();

        $dql = "SELECT u FROM HSUserBundle:User u";
        $users = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            3/*limit per page*/
        );
        
        return $this->render('HSUserBundle:User:index.html.twig', compact('pagination'));
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('HSUserBundle:User');

        $user = $repository->find($id);

        return new Response($user->getUsername());
    }

    public function createAction()
    {
        $user = new User();

        $form = $this->createCreateForm($user);

        return $this->render('HSUserBundle:User:create.html.twig', [
            'form'   => $form->createView(),
            'method' => 'POST'
        ]);
    }

    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, [
            'action' => $this->generateUrl('hs_user_store')
        ]);

        return $form;
    }

    public function storeAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if ($form->isValid())
        {
            $password = $form->get('password')->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $password);

            $user->setPassword($encoded);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('message', 'user_created');

            return $this->redirectToRoute('hs_user_index');
        }

        return $this->render('HSUserBundle:User:create.html.twig', [
            'form'   => $form->createView(),
            'method' => 'POST'
        ]);
    }
}
