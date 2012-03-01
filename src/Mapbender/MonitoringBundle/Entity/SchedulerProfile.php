<?php
namespace Mapbender\MonitoringBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Definition of Scheduler
 * 
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 * @ORM\Entity
 */
class SchedulerProfile {
    private static $HOUR_MS = 3600000;
    private static $STATUS_STARTED = "started";
    private static $STATUS_ENDED = "ended";
    private static $STATUS_RUNNING = "running";
    private static $STATUS_ERROR = "error";
    private static $STATUS_UNDEFINED = "undefined";
    /**
	 *
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
    protected $id;
    /**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
    protected $title;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
    protected $starttime;
    /**
	 *
	 * @ORM\Column(type="integer", nullable="true")
	 */
    protected $starttimeinterval;
    /**
	 *
	 * @ORM\Column(type="integer", nullable="true")
	 */
    protected $monitoringinterval;
    /**
	 *
	 * @ORM\Column(type="integer", nullable="true")
	 */
    protected $jobinterval;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
    protected $laststarttime;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
    protected $lastendtime;
    /**
	 *
	 * @ORM\Column(type="boolean", nullable="true")
	 */
    protected $current = false;
    /**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
    protected $status;
    
    public function __construct() {
        $this->status = SchedulerProfile::$STATUS_UNDEFINED;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getStarttime() {
        return $this->starttime;
    }
    
    public function setStarttime($starttime) {
        $this->starttime = $starttime;
    }
    
    public function getStarttimeinterval() {
        return $this->starttimeinterval;
    }
    
    public function setStarttimeinterval($starttimeinterval) {
        $this->starttimeinterval = $starttimeinterval;
    }
    
    public function getStarttimeintervalOpts() {
        return array(
            SchedulerProfile::$HOUR_MS => "stündlich",
            (SchedulerProfile::$HOUR_MS * 24) => "täglich",
            (SchedulerProfile::$HOUR_MS * 24 * 7) => "wochentlich");
    }
    
    public function getMonitoringinterval() {
        return $this->monitoringinterval;
    }
    
    public function getMonitoringintervalOpts() {
        return array(
            (SchedulerProfile::$HOUR_MS / 6) => "10 min",
            (SchedulerProfile::$HOUR_MS / 3) => "20 min",
            (SchedulerProfile::$HOUR_MS / 2) => "30 min");
    }
    
    public function setMonitoringinterval($monitoringinterval) {
        $this->monitoringinterval = $monitoringinterval;
    }
    
    public function getJobinterval() {
        return $this->jobinterval;
    }
    
    public function getJobintervalOpts() {
        return array(
            (SchedulerProfile::$HOUR_MS / 60) => "1 min",
            (SchedulerProfile::$HOUR_MS / 30) => "2 min",
            (SchedulerProfile::$HOUR_MS / 12) => "5 min",
            (SchedulerProfile::$HOUR_MS / 6) => "10 min");
    }
    
    public function setJobinterval($jobinterval) {
        $this->jobinterval = $jobinterval;
    }
    
    public function getLaststarttime() {
        return $this->laststarttime;
    }
    
    public function setLaststarttime($laststarttime) {
        $this->laststarttime = $laststarttime;
    }
    
    public function getLastendtime() {
        return $this->lastendtime;
    }
    
    public function setLastendtime($lastendtime) {
        $this->lastendtime = $lastendtime;
    }
    
    public function getCurrent() {
        return $this->current;
    }
    
    public function setCurrent($current) {
        $this->current = $current;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function setStatus($status) {
        $this->status = $status;
    }
    
    public function isStatusStarted() {
        return $this->status == SchedulerProfile::$STATUS_STARTED;
    }
    public function isStatusEnded() {
        return $this->status == SchedulerProfile::$STATUS_ENDED;
    }
    public function isStatusError() {
        return $this->status == SchedulerProfile::$STATUS_ERROR;
    }
    public function isStatusRunning() {
        return $this->status == SchedulerProfile::$STATUS_RUNNING;
    }
    public function isStatusUndefined() {
        return $this->status == SchedulerProfile::$STATUS_UNDEFINED;
    }
    
    public function setStatusStatusStarted() {
        $this->status = SchedulerProfile::$STATUS_STARTED;
    }
    public function setStatusStatusEnded() {
        $this->status = SchedulerProfile::$STATUS_ENDED;
    }
    public function setStatusStatusError() {
        $this->status = SchedulerProfile::$STATUS_ERROR;
    }
    public function setStatusStatusRunning() {
        $this->status = SchedulerProfile::$STATUS_RUNNING;
    }
    public function setStatusStatusUndefined() {
        $this->status = SchedulerProfile::$STATUS_UNDEFINED;
    }
}
?>
