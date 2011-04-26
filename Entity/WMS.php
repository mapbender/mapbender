<?php

namespace MB\WMSBundle\Entity;

/**
 * @orm:Entity
 */
class WMS {

    /**
     *  @orm:Id
     *  @orm:Column(type="integer")
     *  @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @orm:Column(type="string")
     */
    protected $title;
    
    /**
     * @orm:Column(type="string")
     */
    protected $name;
    
    /**
     * @orm:Column(type="string")
     */
    protected $abstract;

}
