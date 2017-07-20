<?php


namespace Mapbender\WmsBundle\Entity;


class WmsOrigin
{
    protected $url;
    protected $userName;
    protected $password;

    public function __construct($url, $userName, $password)
    {
        $this->url = trim($url); // no idea why we trim, mirrors old logic from RepositoryController
        $this->userName = $userName;
        $this->password = $password;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getPassword()
    {
        return $this->password;
    }

}
