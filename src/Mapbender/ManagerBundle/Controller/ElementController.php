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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Mapbender\CoreBundle\Component\Application2;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Form\BaseElementType;

class ElementController extends Controller {
    /**
     * Shows form for creating new element
     *
     * @Route("/application/{app_id}/element/new", requirements={
     *     "app_id" = "\d+"})
     * @Method("GET")
     * @Template
     */
    public function newAction($app_id) {
        $application = new Application2($app_id, $this->container);

        // Extract desired class from request
        $class = $this->getRequest()->get('class');
        if(!class_exists($class)) {
            throw new \RuntimeException('An Element class "' . $class
                . '" does not exist.');
        }

        // Extract desired region from request
        $region = $this->getRequest()->get('region');
        if(!$region) {
            throw new \RuntimeException('No region given.');
        }

        // Build type according to desired class
        $type = $class::getFormType();
        $type = $type ? $type : new BaseElementType();
        $entity = new Element();

        // Preset hidden fields
        $entity->setClass($class);
        $entity->setRegion($region);
        $entity->setWeight(0);

        // by default, use element class title for object, too
        $entity->setTitle($class::getTitle());

        $form = $this->createForm($type, $entity);

        return array(
            'application' => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new element from POSTed data
     *
     * @Route("/application/{app_id}/element/new",
     *     requirements={"app_id"="\d+"})
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Element:new.html.twig")
     */
    public function createAction($app_id) {
        $application = new Application2($app_id, $this->container);

        // We have to start with the BaseElementType, and we'll see later
        // how we handle custom form types...
        $entity = new Element();
        $entity->setApplication($application->getEntity());
        $type = new BaseElementType();
        $form = $this->createForm($type, $entity);

        $form->bindRequest($this->getRequest());
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->setFlash('notice',
                'Your element has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'id' => $app_id)));
        } else {
            return array(
                'application' => $application,
                'form' => $form->createView());
        }
    }

    /**
     * @Route("/application/{app_id}/element/{id}", requirements={
     *     "app_id" = "\d+", "id" = "\d+"})
     * @Method("GET")
     * @Template
     */
    public function editAction($app_id, $id) {
        $application = new Application2($app_id, $this->container);

        $entity = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$entity) {
            return $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        $class = $entity->getClass();
        $type = $class::getFormType();
        $type = $type ? $type : new BaseElementType();

        $form = $this->createForm($type, $entity);

        return array(
            'application' => $application,
            'element' => $entity,
            'form' => $form->createView());
    }

    /**
     * Updates element by POSTed data
     *
     * @Route("/application/{app_id}/element/{id}update", requirements = {
     *     "app_id" = "\d+", "id" = "\d+" })
     * @Method("POST")
     */
    public function updateAction($app_id, $id) {
        $application = new Application2($app_id, $this->container);
        $entity = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$entity) {
            return $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        // We have to start with the BaseElementType, and we'll see later
        // how we handle custom form types...
        $type = new BaseElementType();
        $form = $this->createForm($type, $entity);

        $form->bindRequest($this->getRequest());
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->flush();

            $this->get('session')->setFlash('notice',
                'Your element has been updated.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'id' => $app_id)));
        } else {
            return array(
                'application' => $application,
                'form' => $form->createView());
        }

    }
}

