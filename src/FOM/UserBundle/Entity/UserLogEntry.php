<?php
namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'fom_user_log')]
#[ORM\Index(columns: ['ipAddress', 'userName', 'creationDate', 'userId'], name: 'ipNamedRequestDate')]
class UserLogEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $userId;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $userName;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $context;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', nullable: false)]
    protected $ipAddress;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', nullable: false)]
    protected $action;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', nullable: false)]
    protected $status;

    #[ORM\Column(type: 'datetime', nullable: true)]
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

    #[ORM\PrePersist]
    public function createDateOnInsert()
    {
        $this->creationDate = new \DateTime();
    }
}
