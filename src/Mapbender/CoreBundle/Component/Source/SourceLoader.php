<?php


namespace Mapbender\CoreBundle\Component\Source;




use Mapbender\CoreBundle\Entity\Source;

abstract class SourceLoader
{
    public abstract function loadSource(mixed $formData): Source;

    public abstract function getFormType(): string;
}
