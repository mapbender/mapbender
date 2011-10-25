<?php
namespace Mapbender\MonitoringBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Mapbender\MonitoringBundle\Entity\MonitoringJob;
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
}