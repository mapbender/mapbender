<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Contact;
/**
 * Description of Metadata
 *
 * @author Paul Schmidt
 */
class Metadata
{
    protected $contact;
    
    protected $sections;
    
    public function __construct(){
        $this->sections = array();
    }
    
    public function getContact()
    {
        return $this->contact;
    }

    public function setContact(Contact $contact)
    {
        $this->contact = $contact;
    }

        
    public function toArray(){
        $metadata = array(
            "metadata" => array(
                "display" => "notab",
                "sections" => array(
                    array(
                        "title" => "Mytitle",
                        "items" => array(
                            array("name" => "name", "value" => "value"),
                            array("name" => "name", "value" => "value"),
                        )
                    ),
                )
            )
        );
        if($this->contact){
            $metadata['metadata']['sections'] = null;
        }
        return $metadata;
    }
}
