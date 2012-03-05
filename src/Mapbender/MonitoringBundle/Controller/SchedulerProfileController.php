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
//use Mapbender\MonitoringBundle\Form\MonitoringJobType;

/**
 * Description of SchedulerProfileController
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class SchedulerProfileController extends Controller {
	/**
	 * @Route("/scheduler/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function indexAction() {
        $schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
		return array("schedulers" => $schedulers

		);
	}
    
    /**
	 * @Route("/scheduler/new/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function shownewAction() {
        $sp = new SchedulerProfile();
        $form = $this->getNewForm($sp);
		return array('form' => $form->createView(), "scheduler" => $sp);
	}
     /**
	 * @Route("/scheduler/new/")
	 * @Method("POST")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function newAction() {
        $scheduler_req = new SchedulerProfile();
        $form = $this->getNewForm($scheduler_req);
        $form->bindRequest($this->get('request'));
        if($form->isValid()) {
            if($scheduler_req->getCurrent()){
                $this->deactivateAllScheduler();
            }
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($scheduler_req);
            $em->flush();
            $schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
            return $this->render(
                    'MapbenderMonitoringBundle:SchedulerProfile:index.html.twig',
                    array("schedulers" => $schedulers));
        }
        return array('form' => $form->createView(), "scheduler" => $scheduler_req);
	}
    
    /**
	 * @Route("/scheduler/delete/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function deleteAction(SchedulerProfile $sp) {
        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($sp);
        $em->flush();
		$schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
		return array("schedulers" => $schedulers);
	}
    
    /**
	 * @Route("/scheduler/current/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function currentAction(SchedulerProfile $sp) {
        $this->deactivateAllScheduler();
        $em = $this->getDoctrine()->getEntityManager();
        $sp->setCurrent(true);
        $em->persist($sp);
        $em->flush();
        $schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
		return array("schedulers" => $schedulers);
	}
    
    /**
	 * @Route("/scheduler/edit/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	 */
	public function showeditAction(SchedulerProfile $sp) {
        $form = $this->getNewForm($sp);
        return array('form' => $form->createView(), "scheduler" => $sp);
	}
    /**
	* @Route("/scheduler/edit/{spId}")
	* @Method("POST")
	* @Template("MapbenderMonitoringBundle:SchedulerProfile:edit.html.twig")
	*/
	public function editAction(SchedulerProfile $sp) {
//        $scheduler_req = new SchedulerProfile();
        $form = $this->getNewForm($sp);
        $form->bindRequest($this->get('request'));
        if($form->isValid()) {
            if($sp->getCurrent()){
                $this->deactivateAllScheduler();
                $sp->setCurrent(true);
            }
//            $sp->setTitle($scheduler_req->getTitle());
//            $sp->setStarttime($scheduler_req->getStarttime());
//            $sp->setStarttimeinterval($scheduler_req->getStarttimeinterval());
//            $sp->setJobcontinuity($scheduler_req->getJobcontinuity());
//            $sp->setJobinterval($scheduler_req->getJobinterval());
//            $sp->getCurrent($scheduler_req->getCurrent());
            
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($sp);
            $em->flush();
            $schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
            return $this->render(
                    'MapbenderMonitoringBundle:SchedulerProfile:index.html.twig',
                    array("schedulers" => $schedulers));
        }
        return array('form' => $form->createView(), "scheduler" => $sp);
	}
    
    protected function getNewForm(SchedulerProfile $scheduler) {
        $translator = $this->get('translator');
        $formbuilder = $this->createFormBuilder($scheduler)
                ->add('title', 'text', array(
                    'label' => $translator->trans('scheduler_title').":",
                    'required'  => true))
                ->add('starttimeStr', 'text', array(
                    'label' => $translator->trans('scheduler_starttime_(H:M)').":",
                    'required'  => true));
        $startintervalops = $scheduler->getStarttimeintervalOpts();
        $keys = array_keys($startintervalops);
        foreach ($keys as $key){
            $startintervalops[$key] = $translator->trans($startintervalops[$key]);
        }
        $formbuilder->add('starttimeinterval', 'choice', array(
                    'label' => $translator->trans('scheduler_starttimeinterval').":",
                    'choices' => $startintervalops));
        $jobcontinuityops = $scheduler->getJobcontinuityOpts();
        $keys = array_keys($jobcontinuityops);
        foreach ($keys as $key){
            $jobcontinuityops[$key] = $translator->trans($jobcontinuityops[$key]);
        }
        $formbuilder->add('jobcontinuity', 'choice', array(
                    'label' => $translator->trans('job_continuity').":",
                    'choices' => $jobcontinuityops));
        $jobintervalops = $scheduler->getJobintervalOpts();
        $keys = array_keys($jobintervalops);
        foreach ($keys as $key){
            $jobintervalops[$key] = $translator->trans($jobintervalops[$key]);
        }
        $formbuilder->add('jobinterval', 'choice', array(
                    'label' => $translator->trans('scheduler_jobinterval').":",
                    'choices' => $jobintervalops))
                ->add('current', 'checkbox', array(
                        'label' => $translator->trans('scheduler_current').":",
                        'required'  => false));
        return $formbuilder->getForm();
    }
    
    protected function deactivateAllScheduler() {
        $schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
        if($schedulers !== null){
            $em = $this->getDoctrine()->getEntityManager();
            foreach ($schedulers as $schedulerHelp) {
                $schedulerHelp->setCurrent(false);
                $em->persist($schedulerHelp);
                $em->flush();
            }
        }
    }
    
    protected function getCurrentScheduler() {
        return $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findBy(array('current' => true));
    }
    
    /**
	 * @Route("/scheduler/test/")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function testAction() {
                $run = true;
                while($run){
                    $sp = $this->getDoctrine()
                        ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                        ->findOneByCurrent(true);
//                    $sp = $this->getContainer()
//                            ->get("doctrine")
//                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
//                            ->findOneByCurrent(true);
                    if($sp != null){
                        $em = $this->getDoctrine()->getEntityManager();
//                        $em = $this->getDoctrine()->getEntityManager();
                        $timestamp_act = time();
                        if($sp->canStart()) { // check
                            $hour_sec = 3600;
//                            $day_sec = 86400;
                            $sleepbeforstart = 0;
                            $starttimeinterval = $sp->getStarttimeinterval();
                            $timeinterval = $sp->getTimeinterval($starttimeinterval);
                            if($timeinterval != null) {
                                if($timeinterval <= SchedulerProfile::$TIMEINTERVAL_HOURLY) {
//                                    $sleepbeforstart = 0;
                                    $lastendtime = $sp->getLastendtime();
                                    $laststarttime = $sp->getLaststarttime();
                                    if($laststarttime == null) {
                                        $sleepbeforstart = 0;
                                        $timestamp_start = $timestamp_act;
                                    } else {
                                        $timestamp_laststart = date_timestamp_get($laststarttime);
                                        $sleepbeforstart = $timestamp_act - $timestamp_laststart;
                                        if($sleepbeforstart > $hour_sec) {
                                            $sleepbeforstart = 0;
                                            $timestamp_start = $timestamp_act;
                                        } else {
                                            $timestamp_start = $timestamp_laststart + $hour_sec;
                                            $sleepbeforstart = $timestamp_start - $timestamp_act;
                                        }
                                    }
                                    
                                } else {
                                    $starttime = $sp->getStarttime();
                                    $time = date("H:i",date_timestamp_get($starttime));
                                    $timestamp_start = date_timestamp_get(new \DateTime($time));
                                    if($timestamp_start < $timestamp_act){ // start next day
                                        $timestamp_start += $hour_sec * 24;
                                    }
                                    $sleepbeforstart = $timestamp_start - $timestamp_act;
                                    $sp->setStatusWaitstart();
                                    $em->persist($sp);
                                    $em->flush();
        //                            $timestamp = strtotime($starttime);
        //                            $starttime = date("H:i",$timestamp);
        //                            $starttime = new \DateTime(date("H:i",$timestamp));
                                }
                                // sleep
                                sleep($sleepbeforstart);
                                $sp->setLaststarttime(new \DateTime(date("Y-m-d H:i",$timestamp_start)));
                                $jobs = array("test");
                                if(count($jobs)==0){
                                    $sp->setLastendtime(new \DateTime(date("Y-m-d H:i",$timestamp_start)));
                                    $sp->setStatusError();
                                    $em->persist($sp);
                                    $em->flush();
                                    $run = false;
                                } else {
                                    foreach($jobs as $job) {
                                        $sp->setStatusRunning();
                                        $em->persist($sp);
                                        $em->flush();
//                                        $job->mkjob();
                                        sleep(10);
                                        $sp->setStatusWaitjobstart();
                                        $em->persist($sp);
                                        $em->flush();
                                        sleep($sp->getJobinterval());
                                    }
                                    $sp->setLastendtime(new \DateTime(date("Y-m-d H:i", time())));
                                    $sp->setStatusEnded();
                                    $em->persist($sp);
                                    $em->flush();
                                }
//                                    $sp->setLaststarttime(new \DateTime(date("Y-m-d H:i",$timestamp)));
        //                        while(true){
        //                            $this->runCommand($input, $output);
        //                            sleep(10);
        //                        }
                            } else {
                                // timeinterval is null
                            }
                        } else {
                            // status is not ended or undefined
                            $sp->setLaststarttime(new \DateTime(date("Y-m-d H:i",$timestamp_act)));
                            $sp->setLastendtime(new \DateTime(date("Y-m-d H:i",$timestamp_act)));
                            $sp->setStatusCannotstart();
                            $em->persist($sp);
                            $em->flush();
                            $run = false;
                        }
                    } else {
                        // $sp null
                         $run = false;
                    }
                }
//                    if(gettype ($starttime) == "string"){
//                        $timestamp = strtotime($starttime);
//                        $starttime = date("H:i",$timestamp);
//                        $starttime = new \DateTime($starttime);
//                    }

                
		$schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
		return array("schedulers" => $schedulers);
	}
}