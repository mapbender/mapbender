<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Layerset configuration entity
 *
 * @author Christian Wygoda
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_layerset",uniqueConstraints={@UniqueConstraint(name="layerset_idx", columns={"application_id", "title"})})
 * @UniqueEntity(fields={"application", "title"}, message ="Duplicate entry for key 'title'.")
 */
class Layerset
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The layerset title
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="layersets")
     */
    protected $application;

    /**
     * @ORM\OneToMany(targetEntity="SourceInstance", mappedBy="layerset", cascade={"refresh","persist", "remove"})
     * @ORM\JoinColumn(name="instances", referencedColumnName="id")
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $instances;

    public function __construct()
    {
        $this->instances = new ArrayCollection();
    }

    /**
     * Set id. DANGER
     *
     * Set the entity id. DO NOT USE THIS unless you know what you're doing.
     * Probably the only place where this should be used is in the
     * ApplicationYAMLMapper class. Maybe this could be done using a proxy
     * class instead?
     */
    public function setId($id)
    {
        if (null !== $id) {
            $this->id = $id;
        }

        return $this;
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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
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
     * Set application
     *
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Add SourceInstance
     *
     * @param SourceInstance $instance
     */
    public function addInstance(SourceInstance $instance)
    {
        $this->instances->add($instance);
    }

    /**
     * Set instances
     *
     * @param  Doctrine\Common\Collections\Collection $instances
     *                                                           Collection of the SourceInstances
     * @return Layerset
     */
    public function setInstances($instances)
    {
        $this->instances = $instances;

        return $this;
    }

    /**
     * Get instances
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    public function copy(EntityManager $em, &$instanceMap = array())
    {
        $ls = new Layerset();
        $ls->title = $this->title;
        $em->persist($ls);
        foreach ($this->instances as $instance) {
            $cloned = $instance->copy($em);
            $cloned->setLayerset($ls);
            $em->persist($cloned);
            $ls->addInstance($cloned);
            $em->persist($ls);
            $instanceMap[strval($instance->getId())] = $cloned->getId();
        }

        return $ls;
    }

}
