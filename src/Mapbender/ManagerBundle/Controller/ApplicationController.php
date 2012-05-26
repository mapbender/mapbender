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

use Mapbender\CoreBundle\Entity\Application;
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
     * @Route("/applications")
     * @Method("GET")
     * @Template
     */
    public function indexAction() {
        $applications = $this->get('mapbender')->getApplicationEntities();

        return array('applications' => $applications);
    }

    /**
     * Shows form for creating new applications
     *
     * @Route("/application/new")
     * @Method("GET")
     * @Template
     */
    public function newAction() {
        $application = new Application();
        $form = $this->createApplicationForm($application);

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
        $application = new Application();
        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($application);
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
     * Edit application
     *
     * @Route("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template
     */
    public function editAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $form = $this->createApplicationForm($application);

        return array(
            'application' => $application,
            'available_elements' => $this->getElementList(),
            'form' => $form->createView());
    }

    /**
     * Updates application by POSTed data
     *
     * @Route("/application/{slug}/update", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     */
    public function updateAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
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
     * Toggle application state.
     *
     * @Route("/application/{slug}/state", options={"expose"=true})
     * @Method("POST")
     */
    public function toggleStateAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $em = $this->getDoctrine()->getEntityManager();

        $requestedState = $this->get('request')->get('state');
        $currentState = $application->isPublished();
        $newState = $currentState;

        switch($requestedState) {
        case 'enabled':
        case 'disabled':
            $newState = $requestedState === 'enabled' ? true : false;
            $application->setPublished($newState);
            $em->flush();
            $message = 'State switched';
            break;
        case null:
            $message = 'No state given';
            break;
        default:
            $message = 'Unknown state requested';
            break;
        }

        return new Response(json_encode(array(
            'oldState' => $currentState ? 'enabled' : 'disabled',
            'newState' => $newState ? 'enabled' : 'disabled',
            'message' => $message)), 200, array(
                'Content-Type' => 'application/json'));
    }

    /**
     * Delete confirmation page
     * @Route("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template
     */
    public function confirmDeleteAction($slug) {
        $application = new Application2($id, $this->container);
        return array(
            'application' => $application,
            'form' => $this->createDeleteForm($id)->createView());
    }

    /**
     * Delete application
     *
     * @Route("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     */
    public function deleteAction($slug) {
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
                'title' => $elementClassName::getClassTitle(),
                'description' => $elementClassName::getClassDescription(),
                'tags' => $elementClassName::getClassTags());
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

