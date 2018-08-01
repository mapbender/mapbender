<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Mapbender\CoreBundle\Component\Element as ComponentElement;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Form\Type\BaseElementType;
use Mapbender\CoreBundle\Validator\Constraints\ContainsElementTarget;
use Mapbender\CoreBundle\Validator\Constraints\ContainsElementTargetValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\Acl;

/**
 * Class ElementController
 *
 * @package Mapbender\ManagerBundle\Controller
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ElementController extends Controller
{
    /**
     * Show element class selection
     *
     * @ManagerRoute("/application/{slug}/element/select")
     * @Method({"GET","POST"})
     * @Template
     * @param string $slug
     * @return array
     */
    public function selectAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $template    = $application->getTemplate();
        $region      = $this->get('request')->get('region');
        $whitelist   = null;
        $classNames  = null;

        //
        // It's a quick solution for the Responsive template
        // Each template should have a whitelist
        //
        if($template::getTitle() === 'Responsive'){
            $whitelist = $template::getElementWhitelist();
            $whitelist = $whitelist[$region];
        }

        $trans      = $this->container->get('translator');
        $elements   = array();

        //
        // Get only the class names from the whitelist elements, otherwise
        // get them all
        //
        $classNames = ($whitelist) ? $whitelist
                                   : $this->get('mapbender')->getElements();

        foreach ($classNames as $elementClassName) {
            $title = $trans->trans($elementClassName::getClassTitle());
            $tags = array();
            foreach ($elementClassName::getClassTags() as $tag) {
                $tags[] = $trans->trans($tag);
            }
            $elements[$title] = array(
                'class' => $elementClassName,
                'title' => $title,
                'description' => $trans->trans($elementClassName::getClassDescription()),
                'tags' => $tags);
        }

        ksort($elements, SORT_LOCALE_STRING);
        return array(
            'elements' => $elements,
            'region' => $region,
            'whitelist_exists' => ($whitelist !== null)); // whitelist_exists variable can be removed, if every template supports whitelists
    }

    /**
     * Shows form for creating new element
     *
     * @ManagerRoute("/application/{slug}/element/new")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Element:edit.html.twig")
     * @param string $slug
     * @return array Response
     */
    public function newAction($slug)
    {
        /** @var \Mapbender\CoreBundle\Component\Element $elementComponent */
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $class       = $this->getRequest()->get('class'); // Get class for element

        if (!class_exists($class)) {
            throw new \RuntimeException('An Element class "' . $class
                . '" does not exist.');
        }

        $template             = $application->getTemplate();
        $region               = $this->get('request')->get('region');
        $applicationComponent = new ApplicationComponent($this->container, $application);
        $elementComponent     = new $class($applicationComponent, $this->container, ComponentElement::getDefaultElement($class, $region));

        $elementComponent->getEntity()->setTitle($elementComponent->trans($elementComponent->getClassTitle()));

        $response         = ComponentElement::getElementForm($this->container, $application, $elementComponent->getEntity());
        $response["form"] = $response['form']->createView();

        return $response;
    }

    /**
     * Create a new element from POSTed data
     *
     * @ManagerRoute("/application/{slug}/element/new")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Element:edit.html.twig")
     */
    public function createAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $data = $this->get('request')->get('form');
        $element = ComponentElement::getDefaultElement($data['class'],
                $data['region']);
        $element->setApplication($application);
        $form = ComponentElement::getElementForm($this->container, $application,
            $element);
        $form['form']->submit($this->get('request'));

        if ($form['form']->isValid()) {
            $em    = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                "SELECT e FROM MapbenderCoreBundle:Element e"
                . " WHERE e.region=:reg AND e.application=:app");
            $query->setParameters(array(
                "reg" => $element->getRegion(),
                "app" => $element->getApplication()->getId()));
            $elements = $query->getResult();
            $element->setWeight(count($elements) + 1);
            $application = $element->getApplication();
            $this->getDoctrine()->getManager()->persist($application->setUpdated(new \DateTime('now')));
            $em->persist($element);
            $em->flush();
            $entity_class = $element->getClass();
            $appl = new ApplicationComponent($this->container, $application);
            $elComp = new $entity_class($appl, $this->container, $element);
            $elComp->postSave();
            $this->get('session')->getFlashBag()->set('success',
                'Your element has been saved.');

            return new Response('', 201);
        } else {
            return array(
                'form' => $form['form']->createView(),
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

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }
        $form = ComponentElement::getElementForm($this->container, $application,
                $element);

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

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }
        $form = ComponentElement::getElementForm($this->container, $application,
                $element);
//        $form = $this->getElementForm($application, $element);
        $form['form']->submit($this->get('request'));

        if ($form['form']->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $application = $element->getApplication();
            $em->persist($application->setUpdated(new \DateTime('now')));
            $em->persist($element);
            $em->flush();

            $entity_class = $element->getClass();
            $appl = new ApplicationComponent($this->container, $application);
            $elComp = new $entity_class($appl, $this->container, $element);
            $elComp->postSave();
            $this->get('session')->getFlashBag()->set('success',
                'Your element has been saved.');

            return new Response('', 205);
        } else {
            return array(
                'form' => $form['form']->createView(),
                'theme' => $form['theme'],
                'assets' => $form['assets']);
        }
    }

    /**
     * Display and handle element access rights
     *
     * @ManagerRoute("/application/{slug}/element/{id}/security", requirements={"id" = "\d+"})
     * @Template("MapbenderManagerBundle:Element:security.html.twig")
     * @param $slug string Application short name
     * @param $id int Element ID
     * @return array Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function securityAction($slug, $id)
    {
        /** @var EntityManager $entityManager */
        /** @var Element $element */
        $doctrine          = $this->getDoctrine();
        $entityManager     = $doctrine->getManager();
        $elementRepository = $doctrine->getRepository('MapbenderCoreBundle:Element');
        $element           = $elementRepository->findOneById($id);

        if (!$element) {
            throw $this->createNotFoundException("The element with the id \"$id\" does not exist.");
        }

        $entityManager->detach($element); // prevent element from being stored with default config/stored again

        /** @var Form $form */
        /** @var Form $aclForm */
        /** @var Connection  */
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $connection  = $entityManager->getConnection();
        $request     = $this->getRequest();
        $response    = ComponentElement::getElementForm($this->container, $application, $element, true);
        $form        = $response["form"];
        $aclForm     = $form->get('acl');

        if ($request->getMethod() === 'POST' && $form->submit($request)->isValid()) {
            $connection->beginTransaction();
            try {
                $aclManager  = $this->get('fom.acl.manager');
                $application->setUpdated(new \DateTime('now'));
                $entityManager->persist($application);
                $aclManager->setObjectACLFromForm($element, $aclForm, 'object');
                $entityManager->flush();
                $connection->commit();
                $this->get('session')->getFlashBag()->set('success', "Your element's access has been changed.");
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->set('error', "There was an error trying to change your element's access.");
                $connection->rollback();
                $entityManager->close();
                if ($this->container->getParameter('kernel.debug')) {
                    throw($e);
                }
            }
        }

        $response["form"] = $form->createView();
        return $response;
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

        if (!$element) {
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
     */
    public function deleteAction($slug, $id)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        $element = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneById($id);

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            "SELECT e FROM MapbenderCoreBundle:Element e"
            . " WHERE e.region=:reg AND e.application=:app"
            . " AND e.weight>=:min ORDER BY e.weight ASC");
        $query->setParameters(array(
            "reg" => $element->getRegion(),
            "app" => $element->getApplication()->getId(),
            "min" => $element->getWeight()));
        $elements = $query->getResult();
        foreach ($elements as $elm) {
            if ($elm->getId() !== $element->getId()) {
                $elm->setWeight($elm->getWeight() - 1);
            }
        }
        foreach ($elements as $elm) {
            $em->persist($elm);
        }
        $em->remove($element);
        $em->persist($application->setUpdated(new \DateTime('now')));
        $em->flush();

        $this->get('session')->getFlashBag()->set('success',
            'Your element has been removed.');

        return new Response();
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

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }
        $number = $this->get("request")->get("number");
        $newregion = $this->get("request")->get("region");
        if (intval($number) === $element->getWeight() && $element->getRegion() ===
            $newregion) {
            return new Response(json_encode(array(
                    'error' => '',
                    'result' => 'ok')), 200,
                array('Content-Type' => 'application/json'));
        }
        if ($element->getRegion() === $newregion) {
            $em = $this->getDoctrine()->getManager();
            $element->setWeight($number);
            $em->persist($element);
            $em->flush();
            $query = $em->createQuery(
                "SELECT e FROM MapbenderCoreBundle:Element e"
                . " WHERE e.region=:reg AND e.application=:app"
                . " ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $newregion,
                "app" => $element->getApplication()->getId()));
            $elements = $query->getResult();

            $num = 0;
            foreach ($elements as $elm) {
                if ($num === intval($element->getWeight())) {
                    if ($element->getId() === $elm->getId()) {
                        $num++;
                    } else {
                        $num++;
                        $elm->setWeight($num);
                        $num++;
                    }
                } else {
                    if ($element->getId() !== $elm->getId()) {
                        $elm->setWeight($num);
                        $num++;
                    }
                }
            }
            foreach ($elements as $elm) {
                $em->persist($elm);
            }
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime());
            $em->persist($application);
            $em->flush();
        } else {
            // handle old region
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                "SELECT e FROM MapbenderCoreBundle:Element e"
                . " WHERE e.region=:reg AND e.application=:app"
                . " AND e.weight>=:min ORDER BY e.weight ASC");
            $query->setParameters(array(
                "reg" => $element->getRegion(),
                "app" => $element->getApplication()->getId(),
                "min" => $element->getWeight()));
            $elements = $query->getResult();
            foreach ($elements as $elm) {
                if ($elm->getId() !== $element->getId()) {
                    $elm->setWeight($elm->getWeight() - 1);
                }
            }
            foreach ($elements as $elm) {
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
            foreach ($elements as $elm) {
                if ($elm->getId() !== $element->getId()) {
                    $elm->setWeight($elm->getWeight() + 1);
                }
            }
            foreach ($elements as $elm) {
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
                'result' => 'ok')), 200,
            array(
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
        if (!$element) {
            return new Response(json_encode(array(
                    'error' => 'An element with the id "' . $id . '" does not exist.')),
                200, array('Content-Type' => 'application/json'));
        } else {
            $enabled_before = $element->getEnabled();
            $enabled = $enabled === "true" ? true : false;
            $element->setEnabled($enabled);
            $em = $this->getDoctrine()->getManager();
            $em->persist($element->getApplication()->setUpdated(new \DateTime('now')));
            $em->persist($element);
            $em->flush();
            return new Response(json_encode(array(
                    'success' => array(
                        "id" => $element->getId(),
                        "type" => "element",
                        "enabled" => array(
                            'before' => $enabled_before,
                            'after' => $enabled)))), 200,
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

}
