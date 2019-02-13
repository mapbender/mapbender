<?php

namespace Mapbender\PrintBundle\Component\Region;

use Mapbender\PrintBundle\Component\TemplateRegion;

class A4FullPage extends TemplateRegion
{

    public function __construct()
    {
        // hard-coded DIN A4 paper size, no particular offset
        parent::__construct(210, 297, null);
    }
}
