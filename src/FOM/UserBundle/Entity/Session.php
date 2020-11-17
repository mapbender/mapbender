<?php

namespace FOM\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="fom_session")
 */
class Session
{

    /**
     * @var string $id
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $session_id;

    /**
     * @ORM\Column(type="text",nullable=false)
     */
    protected $session_value;

    /**
     * @ORM\Column(type="integer",nullable=false)
     */
    protected $session_time;

    public function getSession_id()
    {
        return $this->session_id;
    }

    public function getSession_value()
    {
        return $this->session_value;
    }

    public function getSession_time()
    {
        return $this->session_time;
    }

    public function setSession_id($session_id)
    {
        $this->session_id = $session_id;
        return $this;
    }

    public function setSession_value($session_value)
    {
        $this->session_value = $session_value;
        return $this;
    }

    public function setSession_time($session_time)
    {
        $this->session_time = $session_time;
        return $this;
    }



}
