<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
class ImportHandler
{
    protected $containter;
    
    public function __construct($container)
    {
        $this->containter = $container;
    }
}
