<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

/**
 * Description of ExportJob
 *
 * @author Paul Schmidt
 */
class ImportJob extends ExchangeJob
{
    
    protected $addApplications;
    
    protected $importFile;
    
    protected $importContent;
    
    public function __construct($format = null)
    {
        parent::__construct($format);
        $this->addApplications = true;
        $this->importFile = null;
    }
    
    public function getAddApplications()
    {
        return $this->addApplications;
    }

    public function setAddApplications($addApplications)
    {
        $this->addApplications = $addApplications;
        return $this;
    }
    public function getImportFile()
    {
        return $this->importFile;
    }

    public function setImportFile($importFile)
    {
        $this->importFile = $importFile;
        return $this;
    }

    public function getImportContent()
    {
        return $this->importContent;
    }

    public function setImportContent($importContent)
    {
        $this->importContent = $importContent;
        return $this;
    }


}
