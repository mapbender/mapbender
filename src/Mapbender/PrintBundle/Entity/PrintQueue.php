<?php
namespace Mapbender\PrintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Entity\User;
use Mapbender\PrintBundle\Component\PrintQueueManager;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PrintQueue
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_print_queue")
 *
 * @package   Mapbender\PrintBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class PrintQueue
{

    /**
     * Unique queue ID
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Salt is a short random string to generate queue token.
     *
     * @ORM\Column(name="salt", type="string", length=10, unique=true)
     * @Assert\Regex(pattern="/^[0-9\-\_a-zA-Z]+$/",message="The salt value is wrong.")
     * @Assert\NotBlank()
     */
    protected $idSalt;

    /**
     * User ID or if anonymous then null
     *
     * @var User
     * @ORM\ManyToOne(targetEntity="FOM\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * The element configuration
     *
     * @var array
     * @ORM\Column(type="json_array",nullable=true)
     */
    protected $payload;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=true)
     */
    protected $priority;

    /**
     * Date of queue creation
     *
     * @var \DateTime
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $queued;

    /**
     * Date of render started
     *
     * @var \DateTime
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $started;

    /**
     * Date of render finished
     *
     * @var \DateTime
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $created;

    /**
     * Is queue finished
     *
     * @return bool
     */
    public function isNew()
    {
        return !$this->started;
    }

    /**
     * Is queue finished
     *
     * @return bool
     */
    public function isFinished()
    {
        return !!$this->created;
    }

    /**
     * Is queue on work
     *
     * @return bool
     */
    public function isOnProgress()
    {
        return !!$this->started && !$this->created;
    }

    /**
     * Get own token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getIdSalt();
    }

    /**
     * Get unique ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set unique ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get unique salt ID
     *
     * @return mixed
     */
    public function getIdSalt()
    {
        return $this->idSalt;
    }

    /**
     * Set unique salt id
     *
     * @param mixed $idSalt
     * @return $this
     */
    public function setIdSalt($idSalt)
    {
        $this->idSalt = $idSalt;
        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user = null )
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get print configuration
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set print configuration
     *
     * @param array $payload
     * @return $this
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set queue priority
     *
     * @param boolean $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get queue creation date
     *
     * @return \DateTime
     */
    public function getQueued()
    {
        return $this->queued;
    }

    /**
     * Set queue creation date
     *
     * @param \DateTime $queued
     * @return $this
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;
        return $this;
    }

    /**
     * Get print render start date
     *
     * @return \DateTime
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Set print render start date
     *
     * @param \DateTime $started
     * @return $this
     */
    public function setStarted($started)
    {
        $this->started = $started;
        return $this;
    }

    /**
     * Get print creation date
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set print creation date
     *
     * @param \DateTime $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }
}