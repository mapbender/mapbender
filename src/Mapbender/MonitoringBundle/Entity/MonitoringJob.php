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
class MonitoringJob {
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
	protected $timestamp;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $latency;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $changed;

	/**
	 *
     * @ORM\ManyToOne(targetEntity="MonitoringDefinition", inversedBy="monitoringJobs")
	 */
	protected $monitoringDefinition;

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
     * Set timestamp
     *
     * @param string $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return string 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set latency
     *
     * @param string $latency
     */
    public function setLatency($latency)
    {
        $this->latency = $latency;
    }

    /**
     * Get latency
     *
     * @return string 
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * Set changed
     *
     * @param string $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * Get changed
     *
     * @return string 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set monitoringDefinition
     *
     * @param Mapbender\MonitoringBundle\Entity\MonitoringDefinition $monitoringDefinition
     */
    public function setMonitoringDefinition(\Mapbender\MonitoringBundle\Entity\MonitoringDefinition $monitoringDefinition)
    {
        $this->monitoringDefinition = $monitoringDefinition;
    }

    /**
     * Get monitoringDefinition
     *
     * @return Mapbender\MonitoringBundle\Entity\MonitoringDefinition 
     */
    public function getMonitoringDefinition()
    {
        return $this->monitoringDefinition;
    }
}