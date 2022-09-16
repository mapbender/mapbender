<?php


namespace Mapbender\CoreBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Symfony\Component\Validator\Constraints;


/**
 * @ORM\MappedSuperclass
 */
abstract class HttpParsedSource extends Source
    implements MutableHttpOriginInterface
{
    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     * @Constraints\NotBlank()
     * @Constraints\Url()
     */
    protected $originUrl = "";

    /**
     * @var string|null
     * @ORM\Column(type="string",nullable=true);
     */
    protected $username = null;

    /**
     * @var string|null
     * @ORM\Column(type="string",nullable=true);
     */
    protected $password = null;

    /**
     * @return string|null
     */
    public function getOriginUrl()
    {
        return $this->originUrl;
    }

    /**
     * @param string|null $originUrl
     */
    public function setOriginUrl($originUrl)
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
