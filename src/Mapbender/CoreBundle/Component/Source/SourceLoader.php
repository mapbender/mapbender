<?php


namespace Mapbender\CoreBundle\Component\Source;




use Mapbender\CoreBundle\Entity\Source;

abstract class SourceLoader
{
    abstract public function loadSource(mixed $formData): Source;

    abstract public function getFormType(): string;

    public function refreshSource(Source $source, mixed $formData): void
    {

    }

    public function getRefreshModel(Source $source): mixed {
        return $source;
    }
}
