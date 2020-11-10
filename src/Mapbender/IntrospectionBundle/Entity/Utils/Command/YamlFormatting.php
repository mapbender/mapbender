<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


class YamlFormatting extends DataItemFormatting
{
    public function __construct($nameKey)
    {
        parent::__construct($nameKey, true, true);
    }
}
