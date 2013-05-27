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
use Symfony\Component\HttpFoundation\Request;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Form\Type\ApplicationType;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Form\Type\LayersetType;
//use Mapbender\CoreBundle\Entity\Layer;
//FIXME: make this work without an explicit import
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApplicationController extends Controller
{

    /**
     * Convenience route, simply redirects to the index action.
     *
     * @ManagerRoute("/application")
     * @Method("GET")
     */
    public function index2Action()
    {
        return $this->redirect(
                        $this->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Render a list of applications the current logged in user has access
     * to.
     *
     * @ManagerRoute("/applications")
     * @Method("GET")
     * @Template
     */
    public function indexAction()
    {
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');

        $applications = $this->get('mapbender')->getApplicationEntities();
        $allowed_applications = array();
        foreach($applications as $application)
        {
            if($securityContext->isGranted('VIEW', $application))
            {
                $allowed_applications[] = $application;
            }
        }

        return array(
            'applications' => $allowed_applications,
            'create_permission' => $securityContext->isGranted('CREATE', $oid)
        );
    }

    /**
     * Shows form for creating new applications
     *
     * @ManagerRoute("/application/new")
     * @Method("GET")
     * @Template
     */
    public function newAction()
    {
        $application = new Application();
        // ACL access check
        $this->checkGranted('CREATE', $application);

        $form = $this->createApplicationForm($application);

        return array(
            'application' => $application,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Create a new application from POSTed data
     *
     * @ManagerRoute("/application")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:new.html.twig")
     */
    public function createAction()
    {
        $application = new Application();
        // ACL access check
        $this->checkGranted('CREATE', $application);

        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid())
        {
            $application->setUpdated(new \DateTime('now'));
            $em = $this->getDoctrine()->getEntityManager();

            $em->getConnection()->beginTransaction();
            $em->persist($application);
            $em->flush();

            $aclManager = $this->get('fom.acl.manager');
            $aclManager->setObjectACLFromForm($application, $form->get('acl'),
                                                                       'object');

            $em->getConnection()->commit();

            $this->get('session')->setFlash('notice',
                                            'Your application has been saved.');

            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_index'));
        }

        return array(
            'application' => $application,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Edit application
     *
     * @ManagerRoute("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template
     */
    public function editAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $form = $this->createApplicationForm($application);

        $templateClass = $application->getTemplate();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
                "SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();

        return array(
            'application' => $application,
            'regions' => $templateClass::getRegions(),
            'slug' => $slug,
            'available_elements' => $this->getElementList(),
            'sources' => $sources,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Updates application by POSTed data
     *
     * @ManagerRoute("/application/{slug}/update", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     */
    public function updateAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $templateClassOld = $application->getTemplate();
        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid())
        {
            $em = $this->getDoctrine()->getEntityManager();

            $em->getConnection()->beginTransaction();
            $templateClassNew = $application->getTemplate();
            $regions = $templateClassNew::getRegions();
            if($templateClassOld !== $templateClassNew && count($regions) > 0)
            {
                foreach($application->getElements() as $element)
                {
                    if(!in_array($element->getRegion(), $regions))
                    {
                        $element->setRegion($regions[0]);
                    }
                }
            }
            $application->setUpdated(new \DateTime('now'));

            try
            {
                $em->flush();

                $aclManager = $this->get('fom.acl.manager');
                $aclManager->setObjectACLFromForm($application,
                                                  $form->get('acl'), 'object');

                $em->getConnection()->commit();

                $this->get('session')->setFlash('notice',
                                                'Your application has been updated.');
            } catch(\Exception $e)
            {

                $this->get('session')->setFlash('error',
                                                'There was an error trying to save your application.');
                $em->getConnection()->rollback();
                $em->close();

                if($this->container->getParameter('kernel.debug'))
                {
                    throw($e);
                }
            }

            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_edit',
                                               array(
                                'slug' => $application->getSlug())));
        }
        $error = "error";

        if(count($form->getErrors()) > 0)
        {
            $error = $form->getErrors();
            $error = $error[0]->getMessageTemplate();
        } else
        {
            foreach($form->getChildren() as $child)
            {
                if(count($child->getErrors()) > 0)
                {
                    $error = $child->getErrors();
                    $error = $error[0]->getMessageTemplate();
                    break;
                }
            }
        }
        $this->get('session')->setFlash('error', $error);

        $templateClass = $application->getTemplate();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
                "SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();
        return new Response($this->container->get('templating')
                                ->render('MapbenderManagerBundle:Application:edit.html.twig',
                                         array(
                                    'application' => $application,
                                    'regions' => $templateClass::getRegions(),
                                    'slug' => $slug,
                                    'available_elements' => $this->getElementList(),
                                    'sources' => $sources,
                                    'form' => $form->createView(),
                                    'form_name' => $form->getName())));
    }

    /**
     * Toggle application state.
     *
     * @ManagerRoute("/application/{slug}/state", options={"expose"=true})
     * @Method("POST")
     */
    public function toggleStateAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $em = $this->getDoctrine()->getEntityManager();

        $requestedState = $this->get('request')->get('state');
        $currentState = $application->isPublished();
        $newState = $currentState;

        switch($requestedState)
        {
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
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:delete.html.twig")
     */
    public function confirmDeleteAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        if($application === null)
        {
            $this->get('session')->setFlash('error',
                                            'Your application has been already deleted.');
            return $this->redirect(
                            $this->generateUrl('mapbender_manager_application_index'));
        }
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $id = $application->getId();
        return array(
            'application' => $application,
            'form' => $this->createDeleteForm($id)->createView());
    }

    /**
     * Delete application
     *
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     */
    public function deleteAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('DELETE', $application);

        $form = $this->createDeleteForm($application->getId());
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid())
        {
            $em = $this->getDoctrine()->getEntityManager();

            $aclProvider = $this->get('security.acl.provider');

            $em->getConnection()->beginTransaction();

            $oid = ObjectIdentity::fromDomainObject($application);
            $aclProvider->deleteAcl($oid);

            $em->remove($application);
            $em->flush();

            $em->commit();

            $this->get('session')->setFlash('notice',
                                            'Your application has been deleted.');
        } else
        {
            $this->get('session')->setFlash('error',
                                            'Your application couldn\'t be deleted.');
        }
        return $this->redirect(
                        $this->generateUrl('mapbender_manager_application_index'));
    }

    /* Layerset block start */

    /**
     * Create a form for a new layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/new")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function newLayersetAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);
        $layerset = new Layerset();
        $layerset->setApplication($application);

        $form = $this->createForm(new LayersetType(), $layerset);

        return array(
            "isnew" => true,
            "application" => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/edit")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function editLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);
        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);

        $form = $this->createForm(new LayersetType(), $layerset);

        return array(
            "isnew" => false,
            "application" => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/create")
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/save")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function saveLayersetAction($slug, $layersetId = null)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $this->checkGranted('EDIT', $application);
        if($layersetId === null)
        { // new object
            $layerset = new Layerset();
            $form = $this->createForm(new LayersetType(), $layerset);
            $layerset->setApplication($application);
        } else
        {
            $layerset = $this->getDoctrine()
                    ->getRepository("MapbenderCoreBundle:Layerset")
                    ->find($layersetId);
            $form = $this->createForm(new LayersetType(), $layerset);
        }
        $form->bindRequest($this->get('request'));
        if($form->isValid())
        {
            $this->getDoctrine()->getEntityManager()->persist($layerset);
            $this->getDoctrine()->getEntityManager()->flush();
            $this->get("logger")->debug("Layerset saved");
            $this->get('session')->setFlash('info', "Layerset saved");
            return $this->redirect($this->generateUrl(
                                    'mapbender_manager_application_edit',
                                    array('slug' => $slug)));
        }
        $this->get('session')->setFlash('error',
                                        'Your form has errors, please review them below.');
        return array(
            "isnew" => $layerset->getId() === null ? true : false,
            "application" => $application,
            'form' => $form->createView());
    }

    /**
     * A confirmation page for a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/confirmdelete")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:deleteLayerset.html.twig")
     */
    public function confirmDeleteLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $this->checkGranted('EDIT', $application);
        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);
        return array(
            'application' => $application,
            'layerset' => $layerset,
            'form' => $this->createDeleteForm($layerset->getId())->createView()
        );
    }

    /**
     * Delete a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/delete")
     * @Method("POST")
     */
    public function deleteLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $this->checkGranted('EDIT', $application);
        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);
        if($layerset !== null)
        {
            $em = $this->getDoctrine()->getEntityManager();

            $em->getConnection()->beginTransaction();

            $em->remove($layerset);
            $em->flush();
            $em->getConnection()->commit();

            $this->get("logger")->debug('The layerset "'
                    . $layerset->getId() . '"has been deleted.');
            $this->get('session')->setFlash('notice',
                                            'Your layerset has been deleted.');
            return $this->redirect($this->generateUrl(
                                    'mapbender_manager_application_edit',
                                    array('slug' => $slug)) . "#layersets");
        }
        $this->get('session')->setFlash('error',
                                        'Your layerset con not be delete.');
        return $this->redirect($this->generateUrl(
                                'mapbender_manager_application_edit',
                                array('slug' => $slug)) . "#layersets");
    }

    /* Layerset block end */

    /* Instance block start */

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/list")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:list-source.html.twig")
     */
    public function listInstanceAction($slug, $layersetId, Request $request)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);

        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
                "SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();

        return array(
            'application' => $application,
            'layerset' => $layerset,
            'sources' => $sources);
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add")
     * @Method("GET")
     */
    public function addInstanceAction($slug, $layersetId, $sourceId,
            Request $request)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);

        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);

        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")
                ->find($sourceId);

        $sourceInstance = $source->createInstance();
        $sourceInstance->setLayerset($layerset);
        $sourceInstance->setWeight(-1);

        $layerset->addInstance($sourceInstance);
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($sourceInstance);
        $em->persist($application);
        $em->persist($layerset);
        $em->flush();

        $num = 0;
        foreach($layerset->getInstances() as $instance)
        {
            $instance->setWeight($num);
            $instance->generateConfiguration();
            $em->persist($instance);
            $em->flush();
            $num++;
        }

        $this->get("logger")->debug('A new instance "'
                . $sourceInstance->getId() . '"has been created. Please edit it!');
        $this->get('session')->setFlash('notice',
                                        'A new instance has been created. Please edit it!');
        return $this->redirect(
                        $this->generateUrl(
                                "mapbender_manager_repository_instance",
                                array("slug" => $slug, "instanceId" => $sourceInstance->getId()))
        );
    }

    /**
     * Confirm removal of a source instance
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/instance/{instanceId}/delete")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:deleteInstance.html.twig")
     */
    public function confirmDeleteInstanceAction($slug, $layersetId, $instanceId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);
        $layerset = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);
        $instance = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:SourceInstance")
                ->find($instanceId);
        return array(
            'application' => $application,
            'layerset' => $layerset,
            'instance' => $instance,
            'form' => $this->createDeleteForm($instance->getId())->createView()
        );
    }

    /**
     * Delete a source instance from a layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/instance/{instanceId}/delete")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:deleteInstance.html.twig")
     */
    public function deleteInstanceAction($slug, $layersetId, $instanceId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted('EDIT', $application);
        $sourceInst = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:SourceInstance")
                ->find($instanceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getSource()->getManagertype()];
        return $this->forward(
                        $manager['bundle'] . ":" . "Repository:deleteInstance",
                        array("slug" => $slug, "instanceId" => $instanceId));
    }

    /* Instance block end */

    /**
     * Create the application form, set extra options needed
     */
    private function createApplicationForm($application)
    {
        $available_templates = array();
        foreach($this->get('mapbender')->getTemplates() as $templateClassName)
        {
            $available_templates[$templateClassName] =
                    $templateClassName::getTitle();
        }
        asort($available_templates);

        return $this->createForm(new ApplicationType(), $application,
                                 array(
                    'available_templates' => $available_templates));
    }

    /**
     * Collect available elements
     */
    private function getElementList()
    {
        $available_elements = array();
        foreach($this->get('mapbender')->getElements() as $elementClassName)
        {
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
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm();
    }

    /**
     * Checks the grant for an action and an object
     * 
     * @param string $action action "CREATE" 
     * @param \Object $object the object
     * @throws AccessDeniedException
     */
    private function checkGranted($action, $object)
    {
        $securityContext = $this->get('security.context');
        if($action === "CREATE")
        {
            $oid = new ObjectIdentity('class', get_class($object));
            if(false === $securityContext->isGranted($action, $oid))
            {
                throw new AccessDeniedException();
            }
        } else if($action === "VIEW" && !$securityContext->isGranted($action,
                                                                     $object))
        {
            throw new AccessDeniedException();
        } else if($action === "EDIT" && !$securityContext->isGranted($action,
                                                                     $object))
        {
            throw new AccessDeniedException();
        } else if($action === "DELETE" && !$securityContext->isGranted($action,
                                                                       $object))
        {
            throw new AccessDeniedException();
        }
    }

}

