<?php

namespace MB\WMSBundle\Entity;

/**
 * @orm:Entity
 */
class WMS {

    /**
     *  @orm:Id
     *  @orm:Column(type="integer")
     */
    protected $id;

    /**
     * @orm:Column(type="string")
     */
    protected $title;
}
