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
    
    protected $addApplication;
    
    protected $importFile;
    
    protected $importContent;
    
    public function __construct($format = null)
    {
        parent::__construct($format);
        $this->addApplication = true;
        $this->importFile = null;
    }
    
    public function getAddApplication()
    {
        return $this->addApplication;
    }

    public function setAddApplication($addApplication)
    {
        $this->addApplication = $addApplication;
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
