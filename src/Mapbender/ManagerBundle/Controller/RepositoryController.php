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
//        $sources = $this->getDoctrine()->getEntityManager()
//                ->findAll("Mapbender\CoreBundle\Entity\Source");
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
        return  $this->forward(
            "MapbenderWmsBundle:Repository:view",
             array("id" => $sourceId)
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
     * deletes a WMS
     * @ManagerRoute("/source/{source}/delete")
     * @Method({"POST"})
    */
    public function deleteAction(Source $source){

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($source);
        $em->flush();
        $this->get('session')->setFlash('info',"Service deleted");
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

}
