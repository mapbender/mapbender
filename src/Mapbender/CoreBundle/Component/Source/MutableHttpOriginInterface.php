<?php


namespace Mapbender\CoreBundle\Component\Source;


interface MutableHttpOriginInterface extends HttpOriginInterface
{
    public function setOriginUrl(string $originUrl): self;

    public function setPassword(string $password): self;

    public function setUsername(string $username): self;
}
