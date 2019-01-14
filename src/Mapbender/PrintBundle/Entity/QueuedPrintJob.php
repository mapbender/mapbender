<?php
namespace Mapbender\PrintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NOTE: table name is chosen for compatibility with older implementations
 * NOTE: 'filename' attribute is mapped to a column named 'salt' for compatibility with older implementations
 *
 * @ORM\Entity(repositoryClass="Mapbender\PrintBundle\Repository\QueuedPrintJobRepository")
 * @ORM\Table(name="mb_print_queue")
 * @ORM\HasLifecycleCallbacks
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class QueuedPrintJob
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * NOTE: column name is chosen for continuity / compatibility with previous implementations.
     *
     * @var string
     * @ORM\Column(name="salt", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    protected $filename;

    /**
     * User ID or if anonymous then null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $userId;

    /**
     * The job serialized into an array
     *
     * @var array
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $payload;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string|null $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
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

    /**
     * @ORM\PostLoad
     */
    public function fixFilename()
    {
        // previous implementations stored the filename without extension
        if ($this->filename && !preg_match('#\.[\w]+$#', $this->filename)) {
            $this->filename .= '.pdf';
        }
    }
}
