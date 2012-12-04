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
use Mapbender\CoreBundle\Entity\Layer;

//FIXME: make this work without an explicit import
use Mapbender\WmsBundle\Entity\WmsInstance;



class ApplicationController extends Controller {
   /**
     * Convenience route, simply redirects to the index action.
     *
     * @ManagerRoute("/application")
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
     * @ManagerRoute("/applications")
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
     * @ManagerRoute("/application/new")
     * @Method("GET")
     * @Template
     */
    public function newAction() {
        $application = new Application();
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
    public function createAction() {
        $application = new Application();
        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
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
            'form' => $form->createView());
    }

    /**
     * Edit application
     *
     * @ManagerRoute("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template
     */
    public function editAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $form = $this->createApplicationForm($application);

        $templateClass = $application->getTemplate();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
                "SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();

        return array(
            'application' => $application,
            'regions' => $templateClass::getRegions(),
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
    public function updateAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $form = $this->createApplicationForm($application);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            
            $em->getConnection()->beginTransaction();

            try {

                $em->flush();

                $aclManager = $this->get('fom.acl.manager');
                $aclManager->setObjectACLFromForm($application,
                    $form->get('acl'), 'object');

                $em->getConnection()->commit();

                $this->get('session')->setFlash('notice',
                    'Your application has been updated.');
            
            } catch(\Exception $e) {
            
                $this->get('session')->setFlash('error',
                    'There was an error trying to save your application.');
                $em->getConnection()->rollback();
                $em->close();
            
                if($this->container->getParameter('kernel.debug'))  {
                    throw($e);
                }   
            }

            return $this->redirect(
                $this->generateUrl('mapbender_manager_application_edit', array(
                    'slug' => $slug)));
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
     * @ManagerRoute("/application/{slug}/state", options={"expose"=true})
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
    * Add a new Source to the Layerset
    * @ManagerRoute("/application/{slug}/layerset")
    * @Method("POST")
    */ 
    public function addLayerset($slug, Request $request){
        $sourceId   = $request->get("sourceId");
        $source     = $this->getDoctrine()
                        ->getRepository("MapbenderCoreBundle:Source")
                        ->find($sourceId);

        $application = $this->get('mapbender')->getApplicationEntity($slug);
        //FIXME: We are working with a single Layerset for now, change this
        // when we need more complex configuration
        $layerset = null;
        if(count($application->getLayersets()) == 0){
            $layerset = new Layerset();
            $layerset->setTitle("main");
            $application->addLayersets($layerset);
            $layerset->setApplication($application);
        }else{
            $layersets = $application->getLayersets();
            $layerset = $layersets[0];
        }
        
        $layer = new Layer();
        $layer->setTitle($source->getTitle());
        $layer->setLayerset($layerset);
        //FIXME: make generic
        $layer->setClass(get_class($source));
        $layer->setWeight("0");
        $sourceInstance = WmsInstance::fromWmsSource($source);
        $layer->setSourceInstance($sourceInstance);
        

        $layerset->addLayers($layer);
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($application);
        $em->persist($layerset);
        $em->persist($layer);
        $em->persist($sourceInstance);
        $em->flush();

        return $this->redirect(
            $this->generateUrl(
                "mapbender_manager_application_edit",
                array("slug" => $slug)
            )
        );
    }

    /**
     * Delete confirmation page
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:delete.html.twig")
     */
    public function confirmDeleteAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
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
    public function deleteAction($slug) {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $form = $this->createDeleteForm($application->getId());
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
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

