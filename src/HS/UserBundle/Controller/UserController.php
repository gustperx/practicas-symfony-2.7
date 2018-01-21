<?php

namespace HS\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//validations (password create)
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

use HS\UserBundle\Entity\User;
use HS\UserBundle\Form\UserType;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     *
     * Index
     */

    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //$users = $em->getRepository('HSUserBundle:User');

        //$users = $users->findAll();

        $dql = "SELECT u FROM HSUserBundle:User u ORDER BY u.id DESC";
        $users = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            15/*limit per page*/
        );

        $deleteFormAjax = $this->createCustomForm(':USER_ID', 'DELETE', 'hs_user_delete');

        return $this->render('HSUserBundle:User:index.html.twig', [
            'pagination'       => $pagination,
            'delete_form_ajax' => $deleteFormAjax->createView()
        ]);
    }

    /**
     * @return Response
     *
     * Create
     */
    public function createAction()
    {
        $user = new User();

        $form = $this->createCreateForm($user);

        return $this->render('HSUserBundle:User:create.html.twig', [
            'form'   => $form->createView(),
            'method' => 'POST'
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * Store
     */
    public function storeAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if ($form->isValid())
        {
            $password = $form->get('password')->getData();

            //validate password
            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);

            if (count($errorList) == 0) {

                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);

                $user->setPassword($encoded);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('message', 'user_created');

                return $this->redirectToRoute('hs_user_index');

            } else {

                $errorMessage = new FormError($errorList[0]->getMessage());

                $form->get('password')->addError($errorMessage);
            }


        }

        return $this->render('HSUserBundle:User:create.html.twig', [
            'form'   => $form->createView(),
            'method' => 'POST'
        ]);
    }


    /**
     * @param $id
     * @return Response
     *
     * View
     */
    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('HSUserBundle:User');

        $user = $repository->find($id);

        if (!$user)
        {
            $messageException = $this->get('translator')->trans('User_not_fount');
            throw $this->createNotFoundException($messageException);
        }

        $deleteForm = $this->createCustomForm($user->getId(), 'DELETE', 'hs_user_delete');


        return $this->render('HSUserBundle:User:view.html.twig', [
            'user' => $user,
            'delete_form' => $deleteForm->createView()
        ]);
    }

    /**
     * @param $id
     * @return Response
     *
     * Edit
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('HSUserBundle:User')->find($id);

        if (!$user)
        {
            $messageException = $this->get('translator')->trans('User_not_fount');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);

        return $this->render('HSUserBundle:User:edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * Update
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('HSUserBundle:User')->find($id);

        if (!$user)
        {
            $messageException = $this->get('translator')->trans('User_not_fount');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $password = $form->get('password')->getData();
            if (!empty($password))
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);

            } else {

                $recoveryPass = $this->recoverPass($id);

                $user->setPassword($recoveryPass[0]['password']);
            }

            if ($form->get('role')->getData() == 'ROLE_ADMIN') {

                $user->setIsActive(1);
            }

            $em->flush();

            $this->addFlash('message', 'user_updated');

            return $this->redirectToRoute('hs_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('HSUserBundle:User:edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('HSUserBundle:User')->find($id);

        if (!$user)
        {
            $messageException = $this->get('translator')->trans('User_not_fount');
            throw $this->createNotFoundException($messageException);
        }

        $allUsers = $em->getRepository('HSUserBundle:User')->findAll();
        $countUsers = count($allUsers);

        //$form = $this->createDeleteForm($user);

        $form = $this->createCustomForm($user->getId(), 'DELETE', 'hs_user_delete');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if ($request->isXmlHttpRequest())
            {
                $res = $this->deleteUser($user->getRole(), $em, $user);

                return new Response(json_encode([
                    'removed' => $res['removed'],
                    'message' => $res['message'],
                    'countUsers' => $countUsers
                ]), 200, ['Content-Type' => 'application/json']);
            }

            $res = $this->deleteUser($user->getRole(), $em, $user);

            $this->addFlash($res['alert'], $res['message']);

            return $this->redirectToRoute('hs_user_index');
        }
    }

    public function deleteUser($role, $em, $user)
    {
        if ($role == 'ROLE_USER')
        {
            $em->remove($user);

            $em->flush();

            $message = 'user_deleted';

            $removed = 1;

            $alert = 'message';
        }
        elseif ($role == 'ROLE_ADMIN')
        {
            $message = 'user_not_deleted';

            $removed = 0;

            $alert = 'error';
        }

        return ['removed' => $removed, 'message' => $message, 'alert' => $alert];
    }

    /**
     * @param User $entity
     * @return \Symfony\Component\Form\Form
     *
     * Private
     */
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, [
            'action' => $this->generateUrl('hs_user_store')
        ]);

        return $form;
    }

    /**
     * @param User $entity
     * @return \Symfony\Component\Form\Form
     *
     * Private
     */
    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, [
            'action' => $this->generateUrl('hs_user_update', ['id' => $entity->getId()]),
            'method' => 'PUT'
        ]);

        return $form;
    }

    /**
     * @param $id
     * @return mixed
     *
     * Private
     */
    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->createQuery('SELECT u.password FROM HSUserBundle:User u WHERE u.id = :id')
            ->setParameter('id', $id);

        $currentPass = $query->getResult();

        return $currentPass;
    }

    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, ['id' => $id]))
            ->setMethod($method)
            ->getForm();
    }
}
