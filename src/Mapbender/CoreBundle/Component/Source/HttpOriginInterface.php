<?php


namespace Mapbender\CoreBundle\Component\Source;


interface HttpOriginInterface
{
    public function getOriginUrl(): string;

    public function getUsername(): ?string;

    public function getPassword(): ?string;
}
