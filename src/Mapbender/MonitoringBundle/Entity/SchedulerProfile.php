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
    public static $HOUR_MS = 3600;
//    public static $STATUS_STARTED = "started";
    private static $STATUS_ENDED = "ended";
    private static $STATUS_RUNNING = "running";
    private static $STATUS_ERROR = "error";
//    public static $STATUS_UNDEFINED = "undefined";
    private static $STATUS_NO_JOB = "no_job";
    private static $STATUS_WAITSTART = "wait_start";
    private static $STATUS_WAITJOBSTART = "wait_job_start";
    private static $STATUS_CANNOTSTART = "can_not_start";
    public static $STATUS_ABORTED = "aborted";
    
    public static $TIMEINTERVAL_14HOURLY = "every quarter of an hour";
    public static $TIMEINTERVAL_12HOURLY = "half-hourly";
    public static $TIMEINTERVAL_HOURLY = "hourly";
    public static $TIMEINTERVAL_DAILY = "daily";
    public static $TIMEINTERVAL_WEEKLY = "weekly";
    public static $TIMEINTERVAL_1MIN = "1 min";
    public static $TIMEINTERVAL_2MIN = "2 min";
    public static $TIMEINTERVAL_3MIN = "3 min";
    public static $TIMEINTERVAL_4MIN = "4 min";
    public static $TIMEINTERVAL_5MIN = "5 min";
    public static $TIMEINTERVAL_10MIN = "10 min";
    /**
	 *
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
    protected $id;
    /**
	 *
	 * @ORM\Column(type="string", nullable=true)
	 */
    protected $title;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
    protected $starttime;
    
    protected $starttimeStr;
    /**
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
    protected $starttimeinterval;
    /**
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
    protected $jobcontinuity;
    /**
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
    protected $jobinterval;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
    protected $laststarttime;
    
    /**
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
    protected $nextstarttime;
    /**
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
    protected $lastendtime;
    /**
	 *
	 * @ORM\Column(type="boolean", nullable=true)
	 */
    protected $current = false;
    /**
	 *
	 * @ORM\Column(type="string", nullable=true)
	 */
    protected $status;
    
    public function __construct() {
//        $this->status = SchedulerProfile::$STATUS_UNDEFINED;
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
        if($starttime == null){
            $this->starttimeStr = $starttime;
        } else if(gettype ($starttime) == "string"){
            $this->starttimeStr = $starttime;
            $timestamp = strtotime($starttime);
            $starttime = date("H:i",$timestamp);
            $starttime = new \DateTime($starttime);
        } else {
            $this->starttimeStr = date("H:i",date_timestamp_get($starttime));
        }
        $this->starttime = $starttime;
    }
    
    public function getStarttimeStr() {
        $starttime = $this->getStarttime();
        if($starttime !=null){
            $this->starttimeStr = date("H:i",date_timestamp_get($this->getStarttime()));
        } else {
            $this->starttimeStr = null;
        }
        return $this->starttimeStr;
    }
    
    public function setStarttimeStr($starttime) {
        $this->starttimeStr = $starttime;
        $this->setStarttime($this->starttimeStr);
    }
    
    public function getStarttimeinterval() {
        return $this->starttimeinterval;
    }
    
    public function setStarttimeinterval($starttimeinterval) {
        $this->starttimeinterval = $starttimeinterval;
    }
    
    public function getStarttimeintervalOpts() {
        return array(
//            // TEST START to delete
//            (SchedulerProfile::$HOUR_MS / 60) => SchedulerProfile::$TIMEINTERVAL_1MIN,
//            (SchedulerProfile::$HOUR_MS / 30) => SchedulerProfile::$TIMEINTERVAL_2MIN,
//            (SchedulerProfile::$HOUR_MS / 20) => SchedulerProfile::$TIMEINTERVAL_3MIN,
//            (SchedulerProfile::$HOUR_MS / 15) => SchedulerProfile::$TIMEINTERVAL_4MIN,
//            (SchedulerProfile::$HOUR_MS / 12) => SchedulerProfile::$TIMEINTERVAL_5MIN,
//            (SchedulerProfile::$HOUR_MS / 6) => SchedulerProfile::$TIMEINTERVAL_10MIN,
//            (SchedulerProfile::$HOUR_MS / 4) => SchedulerProfile::$TIMEINTERVAL_14HOURLY,
//            (SchedulerProfile::$HOUR_MS / 2) => SchedulerProfile::$TIMEINTERVAL_12HOURLY,
//            // TEST END 
            SchedulerProfile::$HOUR_MS => SchedulerProfile::$TIMEINTERVAL_HOURLY,
            (SchedulerProfile::$HOUR_MS * 24) => SchedulerProfile::$TIMEINTERVAL_DAILY,
            (SchedulerProfile::$HOUR_MS * 24 * 7) => SchedulerProfile::$TIMEINTERVAL_WEEKLY);
    }
    
    public function getJobcontinuity() {
        return $this->jobcontinuity;
    }
    
    public function getJobcontinuityOpts() {
        return array(
            (SchedulerProfile::$HOUR_MS / 60) => SchedulerProfile::$TIMEINTERVAL_1MIN,
            (SchedulerProfile::$HOUR_MS / 30) => SchedulerProfile::$TIMEINTERVAL_2MIN,
            (SchedulerProfile::$HOUR_MS / 20) => SchedulerProfile::$TIMEINTERVAL_3MIN,
            (SchedulerProfile::$HOUR_MS / 15) => SchedulerProfile::$TIMEINTERVAL_4MIN,
            (SchedulerProfile::$HOUR_MS / 12) => SchedulerProfile::$TIMEINTERVAL_5MIN);
    }
    
    public function setJobcontinuity($jobcontinuity) {
        $this->jobcontinuity = $jobcontinuity;
    }
    
    public function getJobinterval() {
        return $this->jobinterval;
    }
    
    public function getJobintervalOpts() {
        return array(
            (SchedulerProfile::$HOUR_MS / 60) => SchedulerProfile::$TIMEINTERVAL_1MIN,
            (SchedulerProfile::$HOUR_MS / 30) => SchedulerProfile::$TIMEINTERVAL_2MIN,
            (SchedulerProfile::$HOUR_MS / 12) => SchedulerProfile::$TIMEINTERVAL_5MIN,
            (SchedulerProfile::$HOUR_MS / 6) => SchedulerProfile::$TIMEINTERVAL_10MIN);
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
    
    public function getNextstarttime() {
        return $this->nextstarttime;
    }
    
    public function setNextstarttime($nextstarttime) {
        $this->nextstarttime = $nextstarttime;
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
    
    public function getTimeinterval($timeinterval) {
        switch ($timeinterval) {
        case SchedulerProfile::$HOUR_MS: return SchedulerProfile::$TIMEINTERVAL_HOURLY; break;
        case SchedulerProfile::$HOUR_MS * 24: return SchedulerProfile::$TIMEINTERVAL_DAILY; break;
        case SchedulerProfile::$HOUR_MS * 24 * 7: return SchedulerProfile::$TIMEINTERVAL_WEEKLY; break;
        case SchedulerProfile::$HOUR_MS / 60: return SchedulerProfile::$TIMEINTERVAL_1MIN; break;
        case SchedulerProfile::$HOUR_MS / 30: return SchedulerProfile::$TIMEINTERVAL_2MIN; break;
        case SchedulerProfile::$HOUR_MS / 20: return SchedulerProfile::$TIMEINTERVAL_3MIN; break;
        case SchedulerProfile::$HOUR_MS / 15: return SchedulerProfile::$TIMEINTERVAL_4MIN; break;
        case SchedulerProfile::$HOUR_MS / 12: return SchedulerProfile::$TIMEINTERVAL_5MIN; break;
        case SchedulerProfile::$HOUR_MS / 6: return SchedulerProfile::$TIMEINTERVAL_10MIN; break;

        default:
        return null;
        }
    }
    
    public function canStart() {
        return $this->status == SchedulerProfile::$STATUS_ENDED
                || $this->status == null
                || $this->status == SchedulerProfile::$STATUS_ERROR;
    }
    
    
    public function isStatusCannotstart() {
        return $this->status == SchedulerProfile::$STATUS_CANNOTSTART;
    }
    public function isStatusEnded() {
        return $this->status == SchedulerProfile::$STATUS_ENDED;
    }
    public function isStatusError() {
        return $this->status == SchedulerProfile::$STATUS_ERROR;
    }
    public function isStatusNojob() {
        return $this->status == SchedulerProfile::$STATUS_NO_JOB;
    }
    public function isStatusRunning() {
        return $this->status == SchedulerProfile::$STATUS_RUNNING;
    }
    public function isStatusWaitjobstart() {
        return $this->status == SchedulerProfile::$STATUS_WAITJOBSTART;
    }
    public function isStatusWaitstart() {
        return $this->status == SchedulerProfile::$STATUS_WAITSTART;
    }
    
    public function setStatusCannotstart() {
        $this->status == SchedulerProfile::$STATUS_CANNOTSTART;
    }
    public function setStatusEnded() {
        $this->status = SchedulerProfile::$STATUS_ENDED;
    }
    public function setStatusError() {
        $this->status = SchedulerProfile::$STATUS_ERROR;
    }
    public function setStatusNojob() {
        $this->status = SchedulerProfile::$STATUS_NO_JOB;
    }
    public function setStatusRunning() {
        $this->status = SchedulerProfile::$STATUS_RUNNING;
    }
    public function setStatusWaitjobstart() {
        $this->status = SchedulerProfile::$STATUS_WAITJOBSTART;
    }
    public function setStatusWaitstart() {
        $this->status = SchedulerProfile::$STATUS_WAITSTART;
    }
}
?>
