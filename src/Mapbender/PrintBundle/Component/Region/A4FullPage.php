<?php

namespace Mapbender\PrintBundle\Component\Region;

class A4FullPage extends FullPage
{

    public function __construct()
    {
        // hard-coded DIN A4 paper size, no particular offset
        parent::__construct(210, 297);
    }
}
