<?php
namespace Mapbender\MonitoringBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Mapbender\MonitoringBundle\Entity\MonitoringJob;
use Mapbender\MonitoringBundle\Entity\MonitoringDefinition;
use Symfony\Component\HttpFoundation\Response;
//use Mapbender\MonitoringBundle\Form\MonitoringJobType;

/**
 * Description of MonitoringDefinitionController
 *
 * @author apour
 */
class MonitoringJobController extends Controller {
	/**
	 * @Route("/jobs/")
	 * @Method("GET")
	 * @Template()
	 * @ParamConverter("monitoringJobList",class="Mapbender\MonitoringBundle\Entity\MonitoringJob")
	 */
	public function indexAction(array $monitoringJobList) {
		return array(

		);
	}
    
    /**
	 * @Route("/exportcsv/")
	 * @Method("GET")
	 * @Template()
	 */
	public function exportcsvAction() {
        $mdid = $this->get('request')->get('mdid');
        $jobs = array();
		if($mdid !== null){
            $query = $this->getDoctrine()->getEntityManager()->createQuery(
                    "SELECT j From Mapbender\MonitoringBundle\Entity\MonitoringJob j"
                    ." WHERE j.monitoringDefinition= :md"
                    ." ORDER BY j.timestamp DESC")
                    ->setParameter("md", $mdid);
            $jobs = $query->getResult();
        } else {
            $query = $this->getDoctrine()->getEntityManager()->createQuery(
                    "SELECT j From Mapbender\MonitoringBundle\Entity\MonitoringJob j"
                    ." ORDER BY j.timestamp DESC");
            $jobs = $query->getResult();
        }
        $lines = array();
        $idx = 0;
        $SEPARATOR_VALUE = "\t";
        $SEPARATOR_ROW = "\n";
        $content = "id"
                .$SEPARATOR_VALUE."monitoringdefinition_id"
                .$SEPARATOR_VALUE."result".$SEPARATOR_VALUE."latency"
                .$SEPARATOR_VALUE."changed".$SEPARATOR_VALUE."status".$SEPARATOR_ROW;
        if($jobs !== null && count($jobs) > 0){
            foreach($jobs as $job){
                $content .= $this->checkValue($job->getId())
                        .$SEPARATOR_VALUE.$this->checkValue($job->getMonitoringDefinition()->getId())
                        .$SEPARATOR_VALUE.$this->checkValue($job->getResult())
                        .$SEPARATOR_VALUE.$this->checkValue($job->getLatency())
                        .$SEPARATOR_VALUE.($job->getChanged() ? $this->checkValue("true") : $this->checkValue(null))
                        .$SEPARATOR_VALUE.$this->checkValue($job->getStatus())
                        .$SEPARATOR_ROW;
            }
        }
        $response = new Response();
        $response->setContent($content);
        $response->headers->set("Content-Type", "text/csv; charset=UTF-8");
        $response->headers->set("Content-Disposition", "attachment; filename=csv_export.csv");
        $response->headers->set("Pragma", "no-cache");
        $response->headers->set("Expires", "0");
        return $response;
	}
    protected function checkValue($value){
        if ($value == null || $value == ""){
            return $value;
        } else {
            $value = str_replace('"', '""', $value);
            return '"'.$value.'"';
        }
    }
    
    /**
	 * @Route("/delete/")
	 * @Method("POST")
	 * @Template()
	 */
	public function deleteAction() {
        $mdid = $this->get('request')->get('mdid');
        if($mdid !== null){
            $jobs = $this->getDoctrine()
                        ->getRepository('Mapbender\MonitoringBundle\Entity\MonitoringJob')
                        ->findById($mdid);
//            $query = $this->getDoctrine()->getEntityManager()->createQuery(
//                    "SELECT j From MapbenderMonitoringBundle:MonitoringJob j"
//                    ." WHERE j.monitoringDefinition= :md")
//                    ->setParameter("md", $mdid);
//            $jobs = $query->getResult();
        } else {
            $query = $this->getDoctrine()->getEntityManager()->createQuery(
                    "SELECT j From MapbenderMonitoringBundle:MonitoringJob j");
            $jobs = $query->getResult();
        }
        if($jobs !== null && count($jobs) > 0){
            $em = $this->getDoctrine()->getEntityManager();
            foreach($jobs as $job){
                try {
                    $em->remove($job);
                    $em->flush();
                } catch(\Exception $E) {
                    $this->get("logger")->info("Could not delete monitoring job. ".$E->getMessage());
                    $this->get("session")->setFlash("error","Could not delete monitoring job.");
                }
            }

            $this->get("session")->setFlash("info","Succsessfully deleted.");
        }
        if($mdid !== null){
            return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_edit",array('mdId' => $mdid)));
        } else {
            return $this->redirect($this->generateUrl("mapbender_monitoring_monitoringdefinition_index"));
        }
    }
    /**
	 * @Route("/{mdid}/confirmdelete/")
	 * @Method("GET")
	 * @Template()
	 */
	public function confirmdeleteAction($mdid) {
		$md = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:MonitoringDefinition")
                ->findOneById($mdid);
		return array(
			"md" => $md
		);
	}	
    /**
	 * @Route("/confirmdeleteall/")
	 * @Method("GET")
	 * @Template()
	 */
	public function confirmdeleteallAction() {
		return array(
		);
	}	
}