<?php
namespace Mapbender\MonitoringBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Description of MonitoringDefinition
 * 
 * @author apour
 * @ORM\Entity
 */
class MonitoringDefinition  {
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
	protected $type;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $typeId;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $name;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $title;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $alias;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $url;

	/**
	 * @Assert\NotNull
	 * @ORM\Column(type="string")
	 */
	protected $requestUrl;

	/**
	 *
	 * @ORM\Column(type="text", nullable="true")
	 */
	protected $response;

	/**
	 *
	 * @ORM\Column(type="text", nullable="true")
	 */
	protected $lastResponse;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $contactEmail;

	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $contact;

	/**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
	protected $lastNotificationTime;

    /**
     * @ORM\OneToMany(targetEntity="MonitoringJob",mappedBy="monitoringDefinition", cascade={"persist","remove"})
    */
	protected $monitoringJobs;

	/**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
	protected $ruleStart;

	/**
	 *
	 * @ORM\Column(type="datetime", nullable="true")
	 */
	protected $ruleEnd;

	/**
	 *
	 * @ORM\Column(type="boolean")
	 */
	protected $ruleMonitor = true;

	/**
	 *
	 * @ORM\Column(type="string")
	 */
	protected $enabled = true;

    public function __construct()
    {
        $this->monitoringJobs = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set typeId
     *
     * @param string $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * Get typeId
     *
     * @return string 
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set alias
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get alias
     *
     * @return string 
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set requestUrl
     *
     * @param string $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * Get requestUrl
     *
     * @return string 
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * Set response
     *
     * @param text $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Get response
     *
     * @return text 
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set lastResponse
     *
     * @param text $lastResponse
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;
    }

    /**
     * Get lastResponse
     *
     * @return text 
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Set contactEmail
     *
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get contactEmail
     *
     * @return string 
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Set contact
     *
     * @param string $contact
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get contact
     *
     * @return string 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set lastNotificationTime
     *
     * @param datetime $lastNotificationTime
     */
    public function setLastNotificationTime($lastNotificationTime)
    {
        $this->lastNotificationTime = $lastNotificationTime;
    }

    /**
     * Get lastNotificationTime
     *
     * @return datetime 
     */
    public function getLastNotificationTime()
    {
        return $this->lastNotificationTime;
    }

    /**
     * Set ruleStart
     *
     * @param datetime $ruleStart
     */
    public function setRuleStart($ruleStart)
    {
        $this->ruleStart = $ruleStart;
    }

    /**
     * Get ruleStart
     *
     * @return datetime 
     */
    public function getRuleStart()
    {
        return $this->ruleStart;
    }

    /**
     * Set ruleEnd
     *
     * @param datetime $ruleEnd
     */
    public function setRuleEnd($ruleEnd)
    {
        $this->ruleEnd = $ruleEnd;
    }

    /**
     * Get ruleEnd
     *
     * @return datetime 
     */
    public function getRuleEnd()
    {
        return $this->ruleEnd;
    }

    /**
     * Set ruleMonitor
     *
     * @param boolean $ruleMonitor
     */
    public function setRuleMonitor($ruleMonitor)
    {
        $this->ruleMonitor = $ruleMonitor;
    }

    /**
     * Get ruleMonitor
     *
     * @return boolean 
     */
    public function getRuleMonitor()
    {
        return $this->ruleMonitor;
    }

    /**
     * Set enabled
     *
     * @param string $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Get enabled
     *
     * @return string 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Add monitoringJobs
     *
     * @param Mapbender\MonitoringBundle\Entity\MonitoringJob $monitoringJobs
     */
    public function addMonitoringJob(\Mapbender\MonitoringBundle\Entity\MonitoringJob $monitoringJob)
    {
        $this->monitoringJobs[] = $monitoringJob;
    }

    /**
     * Get monitoringJobs
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getMonitoringJobs()
    {
        return $this->monitoringJobs;
    }
}
