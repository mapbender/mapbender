<?php

/**
 * Mapbender application management
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

use Mapbender\CoreBundle\Component\Application2;
use Mapbender\ManagerBundle\Form\Type\ApplicationType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ApplicationController extends Controller {
   /**
     * Convenience route, simply redirects to the index action.
     *
     * @Route("/application")
     * @Method("GET")
     */
    public function index2Action() {
        return $this->redirect(
            $this->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Render a list of applications the current logged in user has access
     * to.
     *
     * @Route("/applications/{page}", requirements={"page" = "\d+"}, defaults={ "page" = 1 })
     * @Method("GET")
     * @ParamConverter("apps", class="MapbenderCoreBundle:Application")
     * @Template
     */
    public function indexAction($apps) {
        return array('apps' => $apps);
    }

    /**
     * Shows form for creating new applications
     *
     * @Route("/application/new")
     * @Method("GET")
     * @Template
     */
    public function newAction() {
        $application = new Application2(null, $this->container);
        $form = $this->createApplicationForm($application->getEntity());

        return array(
            'application' => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new application from POSTed data
     *
     * @Route("/application")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:new.html.twig")
     */
    public function createAction() {
        $application = new Application2(null, $this->container);
        $form = $this->createApplicationForm($application->getEntity());
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($application->getEntity());
            $em->flush();

            $this->get('session')->setFlash('notice',
                'Your application has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_index'));
        }

        return array(
            'application' => $application,
            'form' => $form->createView());
    }

    /**
     * View application
     *
     * @Route("/application/{id}", requirements = { "id" = "\d+" })
     * @Method("GET")
     * @Template
     */
    public function viewAction($id) {
        return array();
    }

    /**
     * Edit application
     *
     * @Route("/application/{id}/edit", requirements = { "id" = "\d+" })
     * @Method("GET")
     * @Template
     */
    public function editAction($id) {
        $application = new Application2($id, $this->container);
        $form = $this->createApplicationForm($application->getEntity());

        return array(
            'application' => $application,
            'available_elements' => $this->getElementList(),
            'form' => $form->createView());
    }

    /**
     * Updates application by POSTed data
     *
     * @Route("/application/{id}/update", requirements = { "id" = "\d+" })
     * @Method("POST")
     */
    public function updateAction($id) {
        $application = new Application2($id, $this->container);
        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($application);
            $em->flush();

            $this->get('session')->setFlash('notice',
                'Your application has been updated.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_index'));
        }

        $this->get('session')->setFlash('error',
            'Your form has errors, please review them below.');

        return array(
            'application' => $application,
            'form' => $form->createView());
    }

    /**
     * Delete confirmation page
     * @Route("/application/{id}/delete", requirements = { "id" = "\d+" })
     * @Method("GET")
     * @Template
     */
    public function confirmDeleteAction($id) {
        $application = new Application2($id, $this->container);
        return array(
            'application' => $application,
            'form' => $this->createDeleteForm($id)->createView());
    }

    /**
     * Delete application
     *
     * @Route("/application/{id}/delete", requirements = { "id" = "\d+" })
     * @Method("POST")
     */
    public function deleteAction($id) {
        $application = new Application2($id, $this->container);
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($application->getEntity());
            $em->flush();

            $this->get('session')->setFlash('notice',
                'Your application has been deleted.');

        } else {
            $this->get('session')->setFlash('error',
                'Your application couldn\'t be deleted.');
        }
        return $this->redirect(
            $this->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Create the application form, set extra options needed
     */
    private function createApplicationForm($application) {
        $available_templates = array();
        foreach($this->get('mapbender')->getTemplates() as $templateClassName) {
            $available_templates[$templateClassName] =
                $templateClassName::getTitle();
        }
        asort($available_templates);

        return $this->createForm(new ApplicationType(), $application, array(
            'available_templates' => $available_templates));
    }

    /**
     * Collect available elements
     */
    private function getElementList() {
        $available_elements = array();
        foreach($this->get('mapbender')->getElements() as $elementClassName) {
            $available_elements[$elementClassName] = array(
                'title' => $elementClassName::getTitle(),
                'description' => $elementClassName::getDescription(),
                'tags' => $elementClassName::getTags());
        }
        asort($available_elements);

        return $available_elements;
    }

    /**
     * Creates the form for the delete confirmation page
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }

}

