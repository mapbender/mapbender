<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;

//use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Job class 
 *
 * @author Paul Schmidt
 */
class ExchangeJob
{
    const FORMAT_JSON = 'json';
    const FORMAT_YAML = 'yaml';
    protected $addAcl;
    protected $addSources;
    protected $applications;
    protected $format;

    public function __construct($format = 'json')
    {
        $this->applications = new ArrayCollection();
        $this->addAcl = false;
        $this->addSources = false;
        if(self::FORMAT_JSON !== $format && self::FORMAT_YAML !== $format){
            $this->format = self::FORMAT_JSON;
        } else {
            $this->format = $format;
        }
    }

    public function getAddAcl()
    {
        return $this->addAcl;
    }

    public function setAddAcl($addAcl)
    {
        $this->addAcl = $addAcl;
        return $this;
    }

    public function getApplications()
    {
        return $this->applications;
    }

    public function setApplications($applications)
    {
        $this->applications = $applications;
        return $this;
    }
    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

        public function getAddSources()
    {
        return $this->addSources;
    }

    public function setAddSources($addSources)
    {
        $this->addSources = $addSources;
        return $this;
    }

}
