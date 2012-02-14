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
use Mapbender\Component\HTTP\HTTPClient;
use Mapbender\WmsBundle\Entity\WMSService;

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
		return array(
			"mdList" => $monitoringDefinitionList,
			"debug" => print_r($monitoringDefinitionList,true)
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
            "mapbender_wms_wms_index"
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
				$this->get("logger")->error("Could not save monitoring definition. ".$E->getMessage());
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
        $job = $mr->run();
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
}

?>
