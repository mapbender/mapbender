<?php


namespace Mapbender\ManagerBundle\Form\Model;


use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;

class HttpOriginModel implements MutableHttpOriginInterface
{
    protected $originUrl;
    protected $username;
    protected $password;

    /**
     * @return string
     */
    public function getOriginUrl()
    {
        return $this->originUrl;
    }

    /**
     * @param string $originUrl
     * @return $this
     */
    public function setOriginUrl($originUrl)
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
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
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param HttpOriginInterface $other
     * @return static
     */
    public static function extract(HttpOriginInterface $other)
    {
        $instance = new static();
        $instance->setOriginUrl($other->getOriginUrl());
        $instance->setUsername($other->getUsername());
        $instance->setPassword($other->getPassword());
        return $instance;
    }
}
