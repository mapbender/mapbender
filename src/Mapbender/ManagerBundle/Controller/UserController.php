<?php

namespace Mapbender\ManagerBundle\Controller;

use Mapbender\CoreBundle\Entity\User;
use Mapbender\CoreBundle\Security\UserHelper;
use Mapbender\ManagerBundle\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends Controller {
    /**
     * Renders user list.
     *
     * @Route("/user")
     * @Method({ "GET" })
     * @Template
     */
    public function indexAction() {
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
            'SELECT r FROM MapbenderCoreBundle:User r');

        $users = $query->getResult();

        return array(
            'users' => $users);
    }

    /**
     * @Route("/user/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction() {
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user")
     * @Method({ "POST" })
     * @Template("MapbenderManagerBundle:User:new.html.twig")
     */
    public function createAction() {
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        $form->bindRequest($this->get('request'));

        if($form->isValid()) {
            // Set encrypted password and create new salt
            // The unencrypted password is already set on the user!
            $helper = new UserHelper($this->container);
            $helper->setPassword($user, $user->getPassword());

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_user_index'));
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/edit")
     * @Method({ "GET" })
     * @Template
     */
    public function editAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createForm(new UserType(), $user, array(
            'requirePassword' => false));

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/update")
     * @Method({ "POST" })
     * @Template("MapbenderManagerBundle:User:edit.html.twig")
     */
    public function updateAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        // If no password is given, we'll recycle the old one
        $request = $this->get('request');
        $userData = $request->get('user');
        if($userData['password']['first'] === ''
            && $userData['password']['second'] === '') {
            $userData['password'] = array(
                'first' => $user->getPassword(),
                'second' => $user->getPassword());

            $keepPassword = true;
        }

        $form = $this->createForm(new UserType(), $user, array(
            'requirePassword' => false));
        $form->bind($userData);

        if($form->isValid()) {
            if(!$keepPassword) {
                // Set encrypted password and create new salt
                // The unencrypted password is already set on the user!
                $helper = new UserHelper($this->container);
                $helper->setPassword($user, $user->getPassword());
            }

            $em = $this->getDoctrine()->getEntityManager();
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been updated.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_user_index'));

        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/delete")
     * @Method({ "GET" })
     * @Template("MapbenderManagerBundle:User:delete.html.twig")
     */
    public function confirmDeleteAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createDeleteForm($id);

        return array(
            'user' => $user,
            'form' => $form->createView());
    }

    /**
     * @Route("/user/{id}/delete")
     * @Method({ "POST" })
     * @Template
     */
    public function deleteAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($user);
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been deleted.');
        } else {
            $this->get('session')->setFlash('error',
                'The user couldn\'t be deleted.');
        }
        return $this->redirect(
            $this->generateUrl('mapbender_manager_user_index'));
    }

    /**
     * Creates the form for the confirm delete page.
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
}

