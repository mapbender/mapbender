<?php
namespace Mapbender\MonitoringBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Mapbender\MonitoringBundle\Entity\SchedulerProfile;
use Mapbender\MonitoringBundle\Form\SchedulerProfileType;

/**
 * Description of SchedulerProfileController
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class SchedulerProfileController extends Controller {
    
    private static $CMD = "console monitoring:scheduler run schedulerprofilecontroller";
    
    protected function generateCmd(){
        $dir = $this->container->getParameter("kernel.root_dir");
        $cmd = $dir."/".SchedulerProfileController::$CMD;
        return $cmd;
    }
    
    /**
	 * @Route("/scheduler/start/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function startAction() {
        
        $result = $this->getProcess($this->generateCmd());
        
        if(count($result) == 0){
            $cmd_full = $this->generateCmd()." > /tmp/stdout &";
            $res = exec($cmd_full);
            sleep(3);
        }
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                .' ORDER BY sp.title ASC');
        $schedulers = $query->getResult();
        return array(
            "nowtime" => new \DateTime(),
            "schedulers" => $schedulers,
            "process" => $this->getProcessStatus($this->generateCmd())

		);
	}
    
    /**
	 * @Route("/scheduler/stop/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function stopAction() {
        $this->stopScheduler();
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                .' ORDER BY sp.title ASC');
        $schedulers = $query->getResult();
        return array(
            "nowtime" => new \DateTime(),
            "schedulers" => $schedulers,
            "process" => $this->getProcessStatus($this->generateCmd())
		);
	}
    
    protected function stopScheduler(){
        $result = $this->getProcess($this->generateCmd());

        foreach ($result as $process) {
            $cmd_kill = "kill -9 ".$process["PID"];
            $res = exec($cmd_kill);
            $sp = $this->getDoctrine()
                    ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                    ->findOneByCurrent(true);
            $sp->setStatus(SchedulerProfile::$STATUS_ABORTED);
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($sp);
            $em->flush();
        }
    }
    
	/**
	 * @Route("/scheduler/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function indexAction() {
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                .' ORDER BY sp.title ASC');
        $schedulers = $query->getResult();
		return array(
            "nowtime" => new \DateTime(),
            "schedulers" => $schedulers,
            "process" => $this->getProcessStatus($this->generateCmd())
		);
	}
    
    /**
	 * @Route("/scheduler/new/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function shownewAction() {
        $sp = new SchedulerProfile();
//        $form = $this->getNewForm($sp);
        $form = $this->get("form.factory")->create(
				new SchedulerProfileType($sp),
				new SchedulerProfile());
		return array('form' => $form->createView(), "scheduler" => $sp);
	}
    
     /**
	 * @Route("/scheduler/new/")
	 * @Method("POST")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function newAction() {
        $scheduler_req = new SchedulerProfile();
//        $form = $this->getNewForm($scheduler_req);
        $form = $this->get("form.factory")->create(
				new SchedulerProfileType($scheduler_req), $scheduler_req);
        $form->bindRequest($this->get('request'));
        if($form->isValid()) {
            if($scheduler_req->getCurrent()){
                $this->stopScheduler();
                $this->deactivateAllScheduler();
            }
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($scheduler_req);
            $em->flush();
            $query = $this->getDoctrine()->getEntityManager()->createQuery(
                    'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                    .' ORDER BY sp.title ASC');
            $schedulers = $query->getResult();
            return $this->render(
                    'MapbenderMonitoringBundle:SchedulerProfile:index.html.twig',
                    array(
                        "nowtime" => new \DateTime(),
                        "schedulers" => $schedulers,
                        "process" => $this->getProcessStatus($this->generateCmd())));
        }
        return array('form' => $form->createView(), "scheduler" => $scheduler_req);
	}
    
    /**
	 * @Route("/scheduler/confirmdelete/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:confirmdelete.html.twig")
	 */
	public function confirmdeleteAction(SchedulerProfile $sp) {
        
        return array("sp" => $sp);
	}
    
    /**
	 * @Route("/scheduler/delete/{spId}")
	 * @Method("POST")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function deleteAction(SchedulerProfile $sp) {
        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($sp);
        $em->flush();
		$query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                .' ORDER BY sp.title ASC');
        $schedulers = $query->getResult();
        return array(
            "nowtime" => new \DateTime(),
            "schedulers" => $schedulers,
            "process" => $this->getProcessStatus($this->generateCmd()));
	}
    
    /**
	 * @Route("/scheduler/current/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function currentAction(SchedulerProfile $sp) {
        $this->stopScheduler();
        $this->deactivateAllScheduler();
        $em = $this->getDoctrine()->getEntityManager();
        $sp->setCurrent(true);
        $em->persist($sp);
        $em->flush();
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                .' ORDER BY sp.title ASC');
        $schedulers = $query->getResult();
        return array(
            "nowtime" => new \DateTime(),
            "schedulers" => $schedulers,
            "process" => $this->getProcessStatus($this->generateCmd()));
	}
    
    /**
	 * @Route("/scheduler/edit/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function showeditAction(SchedulerProfile $sp) {
//        $form = $this->getNewForm($sp);
        $form = $this->get("form.factory")->create(
            new SchedulerProfileType($sp), $sp);
        return array('form' => $form->createView(), "scheduler" => $sp);
	}
    /**
	* @Route("/scheduler/edit/{spId}")
	* @Method("POST")
	* @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	*/
	public function editAction(SchedulerProfile $sp) {
        $form = $this->get("form.factory")->create(
            new SchedulerProfileType($sp),
            $sp);
        $form->bindRequest($this->get('request'));
        if($form->isValid()) {
            if($sp->getCurrent()){
                $this->stopScheduler();
                $this->deactivateAllScheduler();
                $sp->setCurrent(true);
            }
            
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($sp);
            $em->flush();
            $query = $this->getDoctrine()->getEntityManager()->createQuery(
                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
                    .' ORDER BY sp.title ASC');
            $schedulers = $query->getResult();
            return $this->render(
                    'MapbenderMonitoringBundle:SchedulerProfile:index.html.twig',
                    array(
                        "nowtime" => new \DateTime(),
                        "schedulers" => $schedulers,
                        "process" => $this->getProcessStatus($this->generateCmd())));
        }
        return array('form' => $form->createView(), "scheduler" => $sp);
	}
    
    protected function deactivateAllScheduler() {
        $schedulers = $this->getDoctrine()->getRepository(
                "MapbenderMonitoringBundle:SchedulerProfile")->findAll();
        if($schedulers !== null){
            $em = $this->getDoctrine()->getEntityManager();
            foreach ($schedulers as $schedulerHelp) {
                $schedulerHelp->setCurrent(false);
                $em->persist($schedulerHelp);
                $em->flush();
            }
        }
    }
    
    protected function getProcessStatus($cmd) {
        $result = $this->getProcess($cmd);
        
        if(count($result) > 0){
            return "running";
        } else
            return "not running";
    }
    
    protected function getProcess($cmd) {
        $res_int = -1;
        $res_arr = array();
        $res = exec("ps -aux ", $res_arr, $res_int);
        $teststr = $cmd;
        $result = array();
        $num = 0;
        $header = array();
        foreach ($res_arr as $value) {
            if($num == 0){
                $header = preg_split ("/[\s]+/", $value);
            } else {
                $pos = strpos ($value , $teststr);
                if($pos!== false){
                    $help = preg_split ("/[\s]+/", $value);
                    $temp = array();
                    for($i = 0; $i < count($header); $i++) {
                        $temp[$header[$i]] = $help[$i];
                    }
                    $result[] = $temp;
                }
            }
            $num++;
        }
        return $result;
    }
    
    protected function getCurrentScheduler() {
        return $this->getDoctrine()->getRepository(
                "MapbenderMonitoringBundle:SchedulerProfile")->findBy(array('current' => true));
    }
    
//    /**
//	 * @Route("/scheduler/test/")
//	 * @Method("GET")
//	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
//	 */
//	public function testAction() {
//                $run = true;
//                $num = 0;
//                $sp_start = $this->get("doctrine")
//                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
//                            ->findOneByCurrent(true);
//                if($sp_start != null){
//                    $sp_start->setLaststarttime(null);
//                    $sp_start->setLastendtime(null);
//                    $sp_start->setNextstarttime(null);
//                    $this->get("doctrine")
//                            ->getEntityManager()->persist($sp_start);
//                    $this->get("doctrine")
//                            ->getEntityManager()->flush();
//                }
//                
//                while($run){
//                    $num ++;
//                    $sp = $this->get("doctrine")
//                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
//                            ->findOneByCurrent(true);
//                    if($sp != null && $sp_start->getId() == $sp->getId()){
////                        $timestamp_act = time();
//                        $now =  new \DateTime(date("Y-m-d H:i:s", time()));
//                            $hour_sec = 3600;
//                            $sleepbeforestart = 0;
//                            if($sp->getNextstarttime() === null) { // first time
//                                if($sp->getStarttime() > $now){
//                                    $sleepbeforestart = date_timestamp_get($sp->getStarttime()) - date_timestamp_get($now);
//                                    $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i:s",date_timestamp_get($sp->getStarttime()))));
//                                } else {
//                                    $sleepbeforestart = $hour_sec * 24 - (date_timestamp_get($now) - date_timestamp_get($sp->getStarttime()));
//                                    $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i:s",date_timestamp_get($now) + $sleepbeforestart)));
//                                }
//                            } else {
//                                if($sp->getNextstarttime() <= $now){
//                                    $nextstarttime_stamp =  date_timestamp_get($sp->getNextstarttime());
//                                    $now_stamp =  date_timestamp_get($now);
//                                    while($nextstarttime_stamp < $now_stamp){
//                                        $nextstarttime_stamp += $sp->getStarttimeinterval();
//                                    }
////                                    $sleepbeforestart = date_timestamp_get($nextstarttime) - date_timestamp_get($now);
//                                    $sp->setNextstarttime(null);
//                                    $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i:s", $nextstarttime_stamp)));
//                                }
//                                $sleepbeforestart = date_timestamp_get($sp->getNextstarttime()) - date_timestamp_get($now);
//                            }
//                            $sp->setStatusWaitstart();
//                            $this->get("doctrine")
//                                    ->getEntityManager()->persist($sp);
//                            $this->get("doctrine")
//                                    ->getEntityManager()->flush();
//                            // sleep
////                            sleep($sleepbeforestart);
//                            $now =  new \DateTime(date("Y-m-d H:i:s", time()));
//                            $sp->setLaststarttime($sp->getNextstarttime());
//                            $sp->setNextstarttime(null);
//                            $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i:s", date_timestamp_get($sp->getLaststarttime()) + $sp->getStarttimeinterval())));
//                            $this->get("doctrine")
//                                    ->getEntityManager()->persist($sp);
//                            $this->get("doctrine")
//                                    ->getEntityManager()->flush();
////                            $this->runCommandII($input, $output, $sp);
////                            $now =  new \DateTime(date("Y-m-d H:i:s", time()));
//                            $sp->setLastendtime(new \DateTime(date("Y-m-d H:i:s", time())));
//                            $this->get("doctrine")
//                                    ->getEntityManager()->persist($sp);
//                            $this->get("doctrine")
//                                    ->getEntityManager()->flush();
//
//                    } else {
//                        // $sp null
//                         $run = false;
//                    }
//                    if($num == 3)
//                        $run = false;
//                }
//                
//		$query = $this->getDoctrine()->getEntityManager()->createQuery(
//                'SELECT sp FROM MapbenderMonitoringBundle:SchedulerProfile sp'
//                .' ORDER BY sp.title ASC');
//        $schedulers = $query->getResult();
//		return array(
//            "nowtime" => new \DateTime(),
//            "schedulers" => $schedulers,
//            "process" => $this->getProcessStatus($this->generateCmd()));
//	}
}