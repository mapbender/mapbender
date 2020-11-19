<?php
namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 * @ORM\Entity
 * @ORM\Table(
 *   name="fom_user_log",
 *   indexes={@ORM\Index(name="ipNamedRequestDate", columns={"ipAddress", "userName", "creationDate", "userId"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class UserLogEntry
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank()
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    protected $userName;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $context;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     */
    protected $ipAddress;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     */
    protected $action;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     */
    protected $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $creationDate;

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args = null)
    {
        if (is_array($args)) {
            $this->fill($args);
        }
    }

    /**
     * Fill the object
     *
     * @param array $args
     */
    private function fill(array $args)
    {
        $properties = array_keys(get_object_vars($this));
        foreach ($args as $key => $value) {
            if (in_array($key, $properties)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @ORM\PrePersist()
     */
    public function createDateOnInsert()
    {
        $this->creationDate = new \DateTime();
    }
}