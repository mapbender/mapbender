<?php


namespace Mapbender\ManagerBundle\Form\Model;


use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoaderSettings;

class HttpOriginModel implements MutableHttpOriginInterface, SourceLoaderSettings
{
    protected $originUrl;
    protected $username;
    protected $password;
    protected bool $activateNewLayers = true;
    protected bool $selectNewLayers = true;

    public function getOriginUrl(): string
    {
        return $this->originUrl;
    }

    public function setOriginUrl(string $originUrl): self
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername($username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
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
