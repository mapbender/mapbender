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
use Mapbender\CoreBundle\Component\Element As ComponentElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Form\Type\BaseElementType;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Mapbender\CoreBundle\Validator\Constraints\ContainsElementTarget;
use Mapbender\CoreBundle\Validator\Constraints\ContainsElementTargetValidator;

class ElementController extends Controller
{

    /**
     * Show element class selection
     *
     * @ManagerRoute("/application/{slug}/element/select")
     * @Method("GET")
     * @Template
     */
    public function selectAction($slug)
    {
        $elements = array();
        foreach($this->get('mapbender')->getElements() as $elementClassName)
        {
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
    public function newAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        // Get class for element
        $class = $this->getRequest()->get('class');
        if(!class_exists($class))
        {
            throw new \RuntimeException('An Element class "' . $class
                    . '" does not exist.');
        }

        // Get first region (by default)
        $template = $application->getTemplate();
        $regions = $template::getRegions();
        $region = $this->get('request')->get('region');

        $element = $this->getDefaultElement($class, $region);
        $form = $this->getElementForm($application, $element);

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
    public function createAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $data = $this->get('request')->get('form');
        $element = $this->getDefaultElement($data['class'], $data['region']);
        $element->setApplication($application);
        $form = $this->getElementForm($application, $element);
        $form['form']->bindRequest($this->get('request'));

        if($form['form']->isValid())
        {
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery(
                    "SELECT e FROM MapbenderCoreBundle:Element e"
                    . " WHERE e.region=:reg AND e.application=:app");
            $query->setParameters(array(
                "reg" => $element->getRegion(),
                "app" => $element->getApplication()->getId()));
            $elements = $query->getResult();
            $element->setWeight(count($elements) + 1);
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime());
            $em->persist($element);
            $em->flush();
            $entity_class = $element->getClass();
            $appl = new \Mapbender\CoreBundle\Component\Application($this->container, $application, array());
            $elComp = new $entity_class($appl, $this->container, $element);
            $elComp->postSave();
            $this->get('session')->setFlash('info',
                                            'Your element has been saved.');

            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_edit',
                                               array(
                                'slug' => $slug)) . '#elements');
        } else
        {
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
    public function editAction($slug, $id)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
                ->getRepository('MapbenderCoreBundle:Element')
                ->findOneById($id);

        if(!$element)
        {
            throw $this->createNotFoundException('The element with the id "'
                    . $id . '" does not exist.');
        }

        $form = $this->getElementForm($application, $element);

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
    public function updateAction($slug, $id)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
                ->getRepository('MapbenderCoreBundle:Element')
                ->findOneById($id);

        if(!$element)
        {
            throw $this->createNotFoundException('The element with the id "'
                    . $id . '" does not exist.');
        }

        $form = $this->getElementForm($application, $element);
        $form['form']->bindRequest($this->get('request'));

        if($form['form']->isValid())
        {
            $em = $this->getDoctrine()->getEntityManager();
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime());
            $em->persist($element);
            $em->flush();
            $entity_class = $element->getClass();
            $appl = new \Mapbender\CoreBundle\Component\Application($this->container, $application, array());
            $elComp = new $entity_class($appl, $this->container, $element);
            $elComp->postSave();
            $this->get('session')->setFlash('info',
                                            'Your element has been saved.');

            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_edit',
                                               array(
                                'slug' => $slug)) . '#elements');
        } else
        {
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

        if(!$element)
        {
            throw $this->createNotFoundException('The element with the id "'
                    . $id . '" does not exist.');
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

        if(!$element)
        {
            throw $this->createNotFoundException('The element with the id "'
                    . $id . '" does not exist.');
        }

        $form = $this->createDeleteForm($id);
        $form->bindRequest($this->getRequest());

        if($form->isValid())
        {
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery(
                    "SELECT e FROM MapbenderCoreBundle:Element e"
                    . " WHERE e.region=:reg AND e.application=:app"
                    . " AND e.weight>=:min ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $element->getRegion(),
                "app" => $element->getApplication()->getId(),
                "min" => $element->getWeight()));
            $elements = $query->getResult();
            foreach($elements as $elm)
            {
                if($elm->getId() !== $element->getId())
                {
                    $elm->setWeight($elm->getWeight() - 1);
                }
            }
            foreach($elements as $elm)
            {
                $em->persist($elm);
            }
            $em->remove($element);
            $em->flush();

            $this->get('session')->setFlash('info',
                                            'Your element has been removed.');

            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_edit',
                                               array(
                                'slug' => $slug)) . '#elements');
        } else
        {
            return array(
                'element' => $element,
                'form' => $this->createDeleteForm($id)->createView());
        }
    }

    /**
     * Delete element
     *
     * @ManagerRoute("application/element/{id}/weight")
     * @Method("POST")
     */
    public function weightAction($id)
    {
        $element = $this->getDoctrine()
                ->getRepository('MapbenderCoreBundle:Element')
                ->findOneById($id);

        if(!$element)
        {
            throw $this->createNotFoundException('The element with the id "'
                    . $id . '" does not exist.');
        }
        $number = $this->get("request")->get("number");
        $newregion = $this->get("request")->get("region");
        if(intval($number) === $element->getWeight()
                && $element->getRegion() === $newregion)
        {
            return new Response(json_encode(array(
                                'error' => '',
                                'result' => 'ok')),
                            200,
                            array('Content-Type' => 'application/json'));
        }
        if($element->getRegion() === $newregion)
        {
            $em = $this->getDoctrine()->getEntityManager();
            $element->setWeight($number);
            $em->persist($element);
            $em->flush();
            $query = $em->createQuery(
                    "SELECT e FROM MapbenderCoreBundle:Element e"
                    . " WHERE e.region=:reg AND e.application=:app"
//                    ." AND e.weight>=:min AND e.weight<=:max"
                    . " ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $newregion,
                "app" => $element->getApplication()->getId()));
            $elements = $query->getResult();

            $num = 0;
            foreach($elements as $elm)
            {
                if($num === intval($element->getWeight()))
                {
                    if($element->getId() === $elm->getId())
                    {
                        $num++;
                    } else
                    {
                        $num++;
                        $elm->setWeight($num);
                        $num++;
                    }
                } else
                {
                    if($element->getId() !== $elm->getId())
                    {
                        $elm->setWeight($num);
                        $num++;
                    }
                }
            }
            foreach($elements as $elm)
            {
                $em->persist($elm);
            }
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime());
            $em->persist($application);
            $em->flush();
        } else
        {
            // handle old region
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery(
                    "SELECT e FROM MapbenderCoreBundle:Element e"
                    . " WHERE e.region=:reg AND e.application=:app"
                    . " AND e.weight>=:min ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $element->getRegion(),
                "app" => $element->getApplication()->getId(),
                "min" => $element->getWeight()));
            $elements = $query->getResult();
            foreach($elements as $elm)
            {
                if($elm->getId() !== $element->getId())
                {
                    $elm->setWeight($elm->getWeight() - 1);
                }
            }
            foreach($elements as $elm)
            {
                $em->persist($elm);
            }
            $em->flush();
            // handle new region
            $query = $em->createQuery(
                    "SELECT e FROM MapbenderCoreBundle:Element e"
                    . " WHERE e.region=:reg AND e.application=:app"
                    . " AND e.weight>=:min ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $newregion,
                "app" => $element->getApplication()->getId(),
                "min" => $number));
            $elements = $query->getResult();
            foreach($elements as $elm)
            {
                if($elm->getId() !== $element->getId())
                {
                    $elm->setWeight($elm->getWeight() + 1);
                }
            }
            foreach($elements as $elm)
            {
                $em->persist($elm);
            }
            $em->flush();
            $element->setWeight($number);
            $element->setRegion($newregion);
            $em->persist($element);
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime());
            $em->persist($application);
            $em->flush();
        }
        return new Response(json_encode(array(
                            'error' => '',
                            'result' => 'ok')), 200, array(
                    'Content-Type' => 'application/json'));
    }
    
    /**
     * Delete element
     *
     * @ManagerRoute("application/element/{id}/enable")
     * @Method("POST")
     */
    public function enableAction($id)
    {
        $element = $this->getDoctrine()
                ->getRepository('MapbenderCoreBundle:Element')
                ->findOneById($id);
        
        $enabled = $this->get("request")->get("enabled");
        if(!$element)
        {
            return new Response(
                            json_encode(array(
                                'error' => 'An element with the id "' . $id . '" does not exist.',
                                'result' => 'ok')),
                            200,
                            array('Content-Type' => 'application/json'));
        } else
        {
            $enabled = $enabled === "true"  ? true : false;
            $element->setEnabled($enabled);
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($element);
            $em->flush();
            return new Response(
                            json_encode(array(
                                'error' => '',
                                'result' => 'ok')),
                            200,
                            array('Content-Type' => 'application/json'));
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
    private function getElementForm($application, $element)
    {
        $class = $element->getClass();

        // Create base form shared by all elements
        $formType = $this->createFormBuilder($element)
                ->add('title', 'text')
                ->add('class', 'hidden')
                ->add('region', 'hidden');
        // Get configuration form, either basic YAML one or special form
        $configurationFormType = $class::getType();
        if($configurationFormType === null)
        {
            $formType->add('configuration', new YAMLConfigurationType(),
                           array(
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
        } else
        {
            $type = $class::getType();

            $formType->add('configuration', new $type(),
                           array(
                'application' => $application
            ));
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
        $configuration = $class::getDefaultConfiguration();
//        if(isset($configuration["targets"])){
//            unset($configuration["targets"]);
//        }
        $element
                ->setClass($class)
                ->setRegion($region)
                ->setWeight(0)
                ->setTitle($class::getClassTitle())
                ->setConfiguration($configuration);

        return $element;
    }

}

