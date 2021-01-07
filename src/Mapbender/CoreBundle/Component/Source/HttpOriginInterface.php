<?php


namespace Mapbender\CoreBundle\Component\Source;


interface HttpOriginInterface
{
    /**
     * @return string
     */
    public function getOriginUrl();

    /**
     * @return string|null
     */
    public function getUsername();

    /**
     * @return string|null
     */
    public function getPassword();
}
