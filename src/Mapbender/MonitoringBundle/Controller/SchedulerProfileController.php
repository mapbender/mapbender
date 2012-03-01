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
                $this->deactiveteAllScheduler();
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
        $this->deactiveteAllScheduler();
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
	public function showeditAction(SchedulerProfile $sp) {$form = $this->getNewForm($sp);
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
                $this->deactiveteAllScheduler();
                $sp->setCurrent(true);
            }
//            $sp->setTitle($scheduler_req->getTitle());
//            $sp->setStarttime($scheduler_req->getStarttime());
//            $sp->setStarttimeinterval($scheduler_req->getStarttimeinterval());
//            $sp->setMonitoringinterval($scheduler_req->getMonitoringinterval());
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
                ->add('starttime', 'text', array(
                    'label' => $translator->trans('scheduler_starttime').":",
                    'required'  => false))
                ->add('starttimeinterval', 'choice', array(
                    'label' => $translator->trans('scheduler_starttimeinterval').":",
                    'choices' => $scheduler->getStarttimeintervalOpts()))
                ->add('monitoringinterval', 'choice', array(
                    'label' => $translator->trans('scheduler_monitoringinterval').":",
                    'choices' => $scheduler->getMonitoringintervalOpts()))
                ->add('jobinterval', 'choice', array(
                    'label' => $translator->trans('scheduler_jobinterval').":",
                    'choices' => $scheduler->getJobintervalOpts()))
                ->add('current', 'checkbox', array(
                        'label' => $translator->trans('scheduler_current').":",
                        'required'  => false));
        return $formbuilder->getForm();
    }
    
    protected function deactiveteAllScheduler() {
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
	 * @Route("/scheduler/test/{spId}")
	 * @Method("GET")
	 * @Template("MapbenderMonitoringBundle:SchedulerProfile:index.html.twig")
	 */
	public function testAction(SchedulerProfile $sp) {
        $str = "10:10";
        if (($timestamp = strtotime($str)) === false) {
            echo "The string ($str) is bogus";
        }
//        else {
//            echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);
//        }
        
//        $timestamp = time();
        $datum = date("d.m.Y",$timestamp);
        $uhrzeit = date("H:i",$timestamp);
//        $newdate = date("H:i",$uhrzeit);
//        echo $datum," - ",$uhrzeit," Uhr";
        $time = $sp->getStarttime();
		$schedulers = $this->getDoctrine()->getRepository("MapbenderMonitoringBundle:SchedulerProfile")->findAll();
		return array("schedulers" => $schedulers);
	}
}