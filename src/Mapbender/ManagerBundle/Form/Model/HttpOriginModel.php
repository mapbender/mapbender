<?php


namespace Mapbender\ManagerBundle\Form\Model;


use Mapbender\Component\SourceLoaderSettings;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;

class HttpOriginModel implements MutableHttpOriginInterface, SourceLoaderSettings
{
    protected $originUrl;
    protected $username;
    protected $password;
    protected bool $activateNewLayers = true;
    protected bool $selectNewLayers = true;

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

    public function setActivateNewLayers(bool $activateNewLayers): self
    {
        $this->activateNewLayers = $activateNewLayers;
        return $this;
    }

    public function setSelectNewLayers(bool $selectNewLayers): self
    {
        $this->selectNewLayers = $selectNewLayers;
        return $this;
    }

    public function activateNewLayers(): bool
    {
        return $this->activateNewLayers;
    }

    public function selectNewLayers(): bool
    {
        return $this->selectNewLayers;
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
