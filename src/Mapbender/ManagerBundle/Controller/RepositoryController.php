<?php

/**
 * Mapbender layerset management
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\Source;
//use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter;

/**
 * @ManagerRoute("/repository")
 */
class RepositoryController extends Controller {
    /**
     * Renders the layer service repository.
     *
     * @ManagerRoute("/{page}", defaults={ "page"=1 }, requirements={ "page"="\d+" })
     * @Method({ "GET" })
     * @Template
     */
    public function indexAction($page) {
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
                "SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();
        return array(
            'title' => 'Repository',
            'sources' => $sources,
        );
    }

    /**
     * Renders a list of importers
     *
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction()
    {
        $managers = $this->get('mapbender')->getRepositoryManagers();
        return array(
            'managers' => $managers
        );
    }

    /**
    * @ManagerRoute("/source/{sourceId}")
    * @Method({"GET"})
    * @Template
    */
    public function viewAction($sourceId){
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:view",
             array("id" => $source->getId())
        );
    }
    
    /**
    * @ManagerRoute("/source/{sourceId}/delete")
    * @Method({"GET"})
    * @Template
    * @ParamConverter
    */
    public function confirmdeleteAction(Source $sourceId){
        return array("source" => $sourceId);
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{source}/delete")
     * @Method({"POST"})
    */
    public function deleteAction(Source $source){
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:delete",
                array("sourceId" => $source->getId())
        );
    }
    
    /**
     * 
     * @ManagerRoute("/application/{slug}/instance/{instanceId}")
     */ 
    public function instanceAction($slug, $instanceId){
        $sourceInst = $this->getDoctrine()
                        ->getRepository("MapbenderCoreBundle:SourceInstance")
                        ->find($instanceId);
//        $sourceInst = $mblayer->getSourceInstance();
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:instance",
                array("slug" => $slug, "instanceId" => $sourceInst->getId())
        );
    }
    
    /**
     * 
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/weight/{instanceId}")
     */ 
    public function instanceWeightAction($slug, $layersetId, $instanceId){
        $sourceInst = $this->getDoctrine()
                        ->getRepository("MapbenderCoreBundle:SourceInstance")
                        ->find($instanceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:instanceweight",
                array("slug" => $slug,
                    "layersetId" => $layersetId,
                    "instanceId" => $sourceInst->getId())
        );
    }
    
    /**
     * 
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/enabled/{instanceId}")
     */ 
    public function instanceEnabledAction($slug, $layersetId, $instanceId){
        $sourceInst = $this->getDoctrine()
                        ->getRepository("MapbenderCoreBundle:SourceInstance")
                        ->find($instanceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:instanceenabled",
                array("slug" => $slug,
                    "layersetId" => $layersetId,
                    "instanceId" => $sourceInst->getId())
        );
    }
    
    /**
     * 
     * @ManagerRoute("/application/{slug}/instanceLayer/{instanceId}/weight/{instLayerId}")
     */ 
    public function instanceLayerWeightAction($slug, $instanceId, $instLayerId){
        $sourceInst = $this->getDoctrine()
                        ->getRepository("MapbenderCoreBundle:SourceInstance")
                        ->find($instanceId);
//        $sourceInst = $mblayer->getSourceInstance();
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];
        return  $this->forward(
                $manager['bundle'] . ":" . "Repository:instancelayerpriority",
                array("slug" => $slug,
                    "instanceId" => $sourceInst->getId(),
                    "instLayerId" => $instLayerId)
        );
    }
    
}
