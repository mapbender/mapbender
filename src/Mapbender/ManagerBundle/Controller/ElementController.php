<?php

/**
 * Mapbender application management
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Form\Type\BaseElementType;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;

class ElementController extends Controller {
    /**
     * Show element class selection
     *
     * @ManagerRoute("/application/{slug}/element/select")
     * @Method("GET")
     * @Template
     */
    public function selectAction($slug) {
        $elements = array();
        foreach($this->get('mapbender')->getElements() as $elementClassName) {
            $elements[$elementClassName] = array(
                'title' => $elementClassName::getClassTitle(),
                'description' => $elementClassName::getClassDescription(),
                'tags' => $elementClassName::getClassTags());
        }

        return array(
            'elements' => $elements,
            'region' => $this->get('request')->get('region'));
    }

    /**
     * Shows form for creating new element
     *
     * @ManagerRoute("/application/{slug}/element/new")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Element:edit.html.twig")
     */
    public function newAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        // Get class for element
        $class = $this->getRequest()->get('class');
        if(!class_exists($class)) {
            throw new \RuntimeException('An Element class "' . $class
                . '" does not exist.');
        }

        // Get first region (by default)
        $template = $application->getTemplate();
        $regions = $template::getRegions();
        $region = $this->get('request')->get('region');

        $element = $this->getDefaultElement($class, $region);
        $form = $this->getElementForm($element);

        return array(
            'form' => $form['form']->createView(),
            'theme' => $form['theme'],
            'assets' => $form['assets']);
    }

    /**
     * Create a new element from POSTed data
     *
     * @ManagerRoute("/application/{slug}/element/new")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Element:new.html.twig")
     */
    public function createAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $data = $this->get('request')->get('form');
        $element = $this->getDefaultElement($data['class'], $data['region']);
        $element->setApplication($application);
        $form = $this->getElementForm($element);
        $form['form']->bindRequest($this->get('request'));

        if($form['form']->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($element);
            $em->flush();

            $this->get('session')->setFlash('info',
                'Your element has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'slug' => $slug)) . '#elements');
        } else {
            return array(
                'form' => $form['type']->getForm()->createView(),
                'theme' => $form['theme'],
                'assets' => $form['assets']);
        }
    }

    /**
     * @ManagerRoute("/application/{slug}/element/{id}", requirements={"id" = "\d+"})
     * @Method("GET")
     * @Template
     */
    public function editAction($slug, $id) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        $form = $this->getElementForm($element);

        return array(
            'form' => $form['form']->createView(),
            'theme' => $form['theme'],
            'assets' => $form['assets']);
    }

    /**
     * Updates element by POSTed data
     *
     * @ManagerRoute("/application/{slug}/element/{id}", requirements = {"id" = "\d+" })
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Element:edit.html.twig")
     */
    public function updateAction($slug, $id) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        $form = $this->getElementForm($element);
        $form['form']->bindRequest($this->get('request'));

        if($form['form']->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($element);
            $em->flush();

            $this->get('session')->setFlash('info',
                'Your element has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'slug' => $slug)) . '#elements');
        } else {
            return array(
                'form' => $form['type']->getForm()->createView(),
                'theme' => $form['theme'],
                'assets' => $form['assets']);
        }
    }

    /**
     * Shows delete confirmation page
     *
     * @ManagerRoute("application/{slug}/element/{id}/delete", requirements = {
     *     "id" = "\d+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Element:delete.html.twig")
     */
    public function confirmDeleteAction($slug, $id)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        return array(
            'element' => $element,
            'form' => $this->createDeleteForm($id)->createView());
    }

    /**
     * Delete element
     *
     * @ManagerRoute("application/{slug}/element/{id}/delete")
     * @Method("POST")
     * @Template
     */
    public function deleteAction($slug, $id)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if(!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id .'" does not exist.');
        }

        $form = $this->createDeleteForm($id);
        $form->bindRequest($this->getRequest());

        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($element);
            $em->flush();

            $this->get('session')->setFlash('info',
                'Your element has been removed.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'slug' => $slug)) . '#elements');
        } else {
            return array(
                'element' => $element,
                'form' => $this->createDeleteForm($id)->createView());
        }
    }

    /**
     * Creates the form for the delete confirmation page
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }

    /**
     * Create form for given element
     *
     * @param string $class
     * @return dsd
     */
    private function getElementForm($element)
    {
        $class = $element->getClass();

        // Create base form shared by all elements
        $formType = $this->createFormBuilder($element)
            ->add('title', 'text')
            ->add('class', 'hidden')
            ->add('region', 'hidden');

        // Get configuration form, either basic YAML one or special form
        $configurationFormType = $class::getType();
        if($configurationFormType === null) {
            $formType->add('configuration', new YAMLConfigurationType(), array(
                'required' => false,
                'attr' => array(
                    'class' => 'code-yaml')));
            $formTheme = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
            $formAssets = array(
                'js' => array(
                    'bundles/mapbendermanager/codemirror2/lib/codemirror.js',
                    'bundles/mapbendermanager/codemirror2/mode/yaml/yaml.js',
                    'bundles/mapbendermanager/js/form-yaml.js'),
                'css' => array(
                    'bundles/mapbendermanager/codemirror2/lib/codemirror.css'));
        } else {
            $type = $class::getType();

            $formType->add('configuration', new $type());
            $formTheme = $class::getFormTemplate();
            $formAssets = $class::getFormAssets();
        }

        return array(
            'form' => $formType->getForm(),
            'theme' => $formTheme,
            'assets' => $formAssets);
    }

    /**
     * Create default element
     *
     * @param string $class
     * @param string $region
     * @return Element
     */
    public function getDefaultElement($class, $region)
    {
        $element = new Element();
        $element
            ->setClass($class)
            ->setRegion($region)
            ->setWeight(0)
            ->setTitle($class::getClassTitle())
            ->setConfiguration($class::getDefaultConfiguration());

        return $element;
    }
}

