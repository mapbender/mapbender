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
abstract class Job
{
    protected $acl;
    
    protected $applications;
    
    protected $sources;
    
    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->acl = false;
        $this->sources = false;
    }
    
    public function getAcl()
    {
        return $this->acl;
    }

    public function setAcl($acl)
    {
        $this->acl = $acl;
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

    public function getSources()
    {
        return $this->sources;
    }

    public function setSources($sources)
    {
        $this->sources = $sources;
        return $this;
    }



    
}
