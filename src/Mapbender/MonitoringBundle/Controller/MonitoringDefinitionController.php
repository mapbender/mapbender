<?php
namespace Mapbender\MonitoringBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Mapbender\MonitoringBundle\Entity\MonitoringDefinition;
use Mapbender\MonitoringBundle\Form\MonitoringDefinitionType;
use Mapbender\MonitoringBundle\Component\MonitoringRunner;
use Mapbender\MonitoringBundle\Entity\MonitoringJob;
use Mapbender\Component\HTTP\HTTPClient;
use Mapbender\WmsBundle\Entity\WMSService;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of MonitoringDefinitionController
 *
 * @author apour
 */
class MonitoringDefinitionController extends Controller {
	
	/**
	 * @Route("/")
	 * @Method("GET")
	 * @Template()
	 * @ParamConverter("monitoringDefinitionList",class="Mapbender\MonitoringBundle\Entity\MonitoringDefinition")
	 */
	public function indexAction(array $monitoringDefinitionList) {
        $total = $this->getDoctrine()->getEntityManager()
            ->createQuery("SELECT count(md.id) as total From MapbenderMonitoringBundle:MonitoringDefinition md")
            ->getScalarResult();
        $total = $total[0]['total'];
        $request = $this->get('request');
        $offset = $request->get('usedOffset');
        $limit = $request->get('usedLimit');
        $nextOffset = count($monitoringDefinitionList) < $limit ? $offset : $offset + $limit;
        $prevOffset = ($offset - $limit)  > 0 ? $offset - $limit : 0;
        $lastOffset = ($total - $limit)  > 0 ? $total - $limit : 0;
		return array(
            "offset" => $offset,
            "nextOffset" =>  $nextOffset,
            "prevOffset" => $prevOffset,
            "lastOffset" => $lastOffset,
            "limit" => $limit,
            "total" => $total,
			"mdList" => $monitoringDefinitionList,
		);
	}
	
	/**
	 * @Route("/create")
	 * @Method("GET")
	 * @Template()
	 */
	public function createAction() {
		$form = $this->get("form.factory")->create(
				new MonitoringDefinitionType(),
				new MonitoringDefinition()
		);
		
		
		return array(
			"form" => $form->createView()
		);
	}
	
    /**
	 * @Route("/wms/{wmsId}")
	 * @Method("POST")
	 * @Template()
	 */
	public function importAction(WMSService $wms) {
        $md = new MonitoringDefinition(); 
        $md->setType(get_class($wms));
        $md->setTypeId($wms->getId());
        $md->setName($wms->getName());
        $md->setTitle($wms->getTitle());
        $md->setRequestUrl($wms->getOnlineResource());

        $em = $this->getDoctrine()
            ->getEntityManager();
        $em->persist($md);
        $em->flush();
        return $this->redirect($this->generateUrl(
            "mapbender_monitoring_monitoringdefinition_edit",
            array("mdId" => $md->getId())
        ));
	}
	
	/**
	 * @Route("/")
	 * @Method("POST")
	 */
	public function addAction() {
		$md = new MonitoringDefinition();
		
		$form = $this->get("form.factory")->create(
				new MonitoringDefinitionType(),
				$md
		);
		
		$request = $this->get("request");
		
		$form->bindRequest($request);
		
		if($form->isValid()) {
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($md);
			$em->flush();
			return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_index"));
		} else {
			return $this->render(
				"MapbenderMonitoringBundle:MonitoringDefinition:create.html.twig",
				array("form" => $form->createView())
			);
		}
	}
	
	
	/**
	 * @Route("/{mdId}")
	 * @Method("GET")
	 * @Template()
	 */
	public function editAction(MonitoringDefinition $md) {
		$form = $this->get("form.factory")->create(
				new MonitoringDefinitionType(),
				$md
		);
		
		return array(
			"form" => $form->createView(),
			"md" => $md
		);
	}
    
	/**
	 * @Route("/{mdId}/delete")
	 * @Method("POST")
	 */
	public function deleteAction(MonitoringDefinition $md) {
		$em = $this->getDoctrine()->getEntityManager();
		try {
			$em->remove($md);
//			$em->remove(null);
			$em->flush();
		} catch(\Exception $E) {
			$this->get("logger")->info("Could not delete monitoring definition. ".$E->getMessage());
			$this->get("session")->setFlash("error","Could not delete monitoring definition.");
			return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_index"));
		}
		
		$this->get("session")->setFlash("info","Succsessfully deleted.");
		return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_index"));
	}	
	
	/**
	 * @Route("/{mdId}/delete")
	 * @Method("GET")
	 * @Template()
	 */
	public function confirmDeleteAction(MonitoringDefinition $md) {
		
		return array(
			"md" => $md
		);
	}	
	
	/**
	 * @Route("/{mdId}")
	 * @Method("POST")
	 */
	public function saveAction(MonitoringDefinition $md) {
		$form = $this->get("form.factory")->create(
				new MonitoringDefinitionType(),
				$md
		);
		
		$request = $this->get("request");
		
		$form->bindRequest($request);
		
		if($form->isValid()) {	
			try {
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($md);
				$em->flush();
			} catch(\Exception $E) {
				$this->get("logger")->err("Could not save monitoring definition. ".$E->getMessage());
				$this->get("session")->setFlash("error","Could not save monitoring definition");
				return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_edit",array("mdId" => $md->getId())));
			}
			return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_index"));
		} else {
			return $this->render(
				"MapbenderMonitoringBundle:MonitoringDefinition:edit.html.twig",
				array("form" => $form->createView(),
					"md" => $md)
			);
		}
	}
	
    /**
	 * @Route("/{mdId}/run")
	 * @Method("POST")
	 */
	public function runAction(MonitoringDefinition $md) {
        $client = new HTTPClient($this->container);
        $mr = new MonitoringRunner($md,$client);
//        $job = $mr->run();
//        if($md->getLastMonitoringJob()){
//            if(strcmp($job->getResult(), $md->getLastMonitoringJob()->getResult()) != 0){
//                $job->setChanged(true);
//            } else {
//                $job->setChanged(false);
//            }
//        }else {
//            $job->setChanged(true);
//        }
//        $md->addMonitoringJob($job);
        
        
        $mr = new MonitoringRunner($md, $client);
        if($md->getEnabled()){
            if($md->getRuleMonitor()){
                $now = new \DateTime();
                if($now > $md->getRuleStart() && $now < $md->getRuleEnd()){
                    $job = $mr->run();
                } else {
                    $job = new MonitoringJob();
                    $job->setMonitoringDefinition($md);
                    $job->setSTATUS(MonitoringJob::$STATUS_EXCEPT_TIME);
                }
            } else {
                $job = $mr->run();                                                  
            }
        } else {
            $job = new MonitoringJob();
            $job->setMonitoringDefinition($md);
            $job->setSTATUS(MonitoringJob::$STATUS_DISABLED);
        }
        
        if($md->getLastMonitoringJob()){
            if(strcmp($job->getResult(), $md->getLastMonitoringJob()->getResult()) != 0){
                $job->setChanged(true);
            } else {
                $job->setChanged(false);
            }
        }else {
            $job->setChanged(true);
        }
        
        $md->addMonitoringJob($job);
        
        
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($md);
        $em->flush();
    	return $this->redirect(
            $this->generateUrl(
                "mapbender_monitoring_monitoringdefinition_edit",
                array("mdId" =>  $md->getId())
            )
        );
    }
    
    /**
	 * @Route("/{mdId}/statreset")
	 * @Method("POST")
	 */
	public function statresetAction(MonitoringDefinition $md) {
        $em = $this->getDoctrine()->getEntityManager();
        foreach($md->getMonitoringJobs() as $job){
            $em->remove($job);
        } 
        $em->flush();
    	return $this->redirect(
            $this->generateUrl(
                "mapbender_monitoring_monitoringdefinition_edit",
                array("mdId" =>  $md->getId())
            )
        );
    }
    
     /**
	 * @Route("/show/{jId}")
	 * @Method("GET")
	 * @Template()
	 */
	public function showAction($jId) {
        $tr = $this->get('translator');
        $job = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:MonitoringJob")
                ->findOneById($jId);
        $result = array("html" => "<pre>".htmlentities($job->getResult())."</pre>",
            "error" => "", "title" => $tr->trans('Job_result'));
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
	}
}

?>
