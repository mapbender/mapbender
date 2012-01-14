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
     * @Route("/applications/{page}", requirements={"page" = "\d+"}, defaults={ "page" = 1 })
     * @Method("GET")
     * @ParamConverter("apps", class="MapbenderCoreBundle:Application")
     * @Template
     */
    public function indexAction($apps) {
        return array('apps' => $apps);
    }

    /**
     * GET applications/search{term}/{page}
     */

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

        $this->get('session')->setFlash('error',
            'Your form has errors, please review them below.');

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
        $application = $this->findApplication($id);
        $form = $this->createApplicationForm($application);

        return array(
            'application' => $application,
            'form' => $form->createView());
    }

    /**
     * Updates application by POSTed data
     *
     * @Route("/application/{id}/update", requirements = { "id" = "\d+" })
     * @Method("POST")
     */
    public function updateAction($id) {
        $application  = $this->findApplication($id);
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
        $application = $this->findApplication($id);
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
        $application = $this->findApplication($id);
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($application);
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
     * Return list of element classes
     *
     * @Route("/application/elements", defaults={ "_format"="json" })
     */
    public function elementListAction() {
        $available_elements = array();
        foreach($this->get('mapbender')->getElements() as $elementClassName) {
            $available_elements[$elementClassName] = array(
                'title' => $elementClassName::getTitle(),
                'description' => $elementClassName::getDescription(),
                'tags' => $elementClassName::getTags());
        }
        asort($available_elements);

        return new Response(json_encode($available_elements));
    }

    /**
     * Creates the form for the delete confirmation page
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }

    /**
     * Find the application with the given id or throw an 404
     */
    private function findApplication($id) {
        $application = $this->get('doctrine')
            ->getRepository('MapbenderCoreBundle:Application')
            ->find($id);
        if($application === null) {
            throw $this->createNotFoundException('Application with id ' . $id
               .' not found');
        }
        return $application;
    }
}

