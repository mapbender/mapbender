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
use Symfony\Component\HttpFoundation\Response;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\Source;

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
    * @ManagerRoute("/source/{sourceId}/confirmdelete")
    * @Method({"GET"})
    * @Template
    */
    public function confirmdeleteAction($sourceId){
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        return array("source" => $source);
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{sourceId}/delete")
     * @Method({"POST"})
    */
    public function deleteAction($sourceId){
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
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
        $number = $this->get("request")->get("number");
        $layersetId_new = $this->get("request")->get("new_layersetId");
        $instance = $this->getDoctrine()
                ->getRepository('MapbenderWmsBundle:WmsInstance')
                ->findOneById($instanceId);
        
        if(!$instance)
        {
            throw $this->createNotFoundException('The wms instance with"
                ." the id "' . $instanceId . '" does not exist.');
        }
        if(intval($number) === $instance->getWeight() && $layersetId === $layersetId_new)
        {
            return new Response(json_encode(array(
                                'error' => '',
                                'result' => 'ok')), 200,
                            array('Content-Type' => 'application/json'));
        }
        
        if($layersetId === $layersetId_new)
        {
            $em = $this->getDoctrine()->getEntityManager();
            $instance->setWeight($number);
            $em->persist($instance);
            $em->flush();
            $query = $em->createQuery(
                    "SELECT i FROM MapbenderWmsBundle:WmsInstance i"
                    . " WHERE i.layerset=:lsid ORDER BY i.weight ASC");
            $query->setParameters(array("lsid" => $layersetId));
            $instList = $query->getResult();

            $num = 0;
            foreach($instList as $inst)
            {
                if($num === intval($instance->getWeight()))
                {
                    if($instance->getId() === $inst->getId())
                    {
                        $num++;
                    } else
                    {
                        $num++;
                        $inst->setWeight($num);
                        $num++;
                    }
                } else
                {
                    if($instance->getId() !== $inst->getId())
                    {
                        $inst->setWeight($num);
                        $num++;
                    }
                }
            }
            foreach($instList as $inst)
            {
                $em->persist($inst);
            }
            $em->flush();
        } else
        {
            $layerset_new = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId_new);
            $em = $this->getDoctrine()->getEntityManager();
            $instance->setLayerset($layerset_new);
            $layerset_new->addInstance($instance);
            $instance->setWeight($number);
            $em->persist($layerset_new);
            $em->persist($instance);
            $em->flush();
            
            // order instances of the old layerset
            $query = $em->createQuery(
                    "SELECT i FROM MapbenderWmsBundle:WmsInstance i"
                    . " WHERE i.layerset=:lsid ORDER BY i.weight ASC");
            $query->setParameters(array("lsid" => $layersetId));
            $instList = $query->getResult();

            $num = 0;
            foreach($instList as $inst)
            {
                $inst->setWeight($num);
                $em->persist($inst);
                $num++;
            }
            $em->flush();
            
            // order instances of the new layerset 
            $query = $em->createQuery(
                    "SELECT i FROM MapbenderWmsBundle:WmsInstance i"
                    . " WHERE i.layerset=:lsid ORDER BY i.weight ASC");
            $query->setParameters(array("lsid" => $layersetId_new));
            $instList = $query->getResult();
            $num = 0;
            foreach($instList as $inst)
            {
                if($num === intval($instance->getWeight()))
                {
                    if($instance->getId() === $inst->getId())
                    {
                        $num++;
                    } else
                    {
                        $num++;
                        $inst->setWeight($num);
                        $num++;
                    }
                } else
                {
                    if($instance->getId() !== $inst->getId())
                    {
                        $inst->setWeight($num);
                        $num++;
                    }
                }
            }
            foreach($instList as $inst)
            {
                $em->persist($inst);
                $em->flush();
            }
            
        }

        return new Response(json_encode(array(
                            'error' => '',
                            'result' => 'ok')), 200, array(
                    'Content-Type' => 'application/json'));
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
