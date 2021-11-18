<?php

namespace OwsProxy3\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Source entity
 *
 * @author Vadim Hermann
 *
 * @ORM\Entity
 * @ORM\Table(name="owsproxy_log")
 * @deprecated leftover from request logging removed in v3.2; keeping Entity for another release avoids schema changes
 *     and failing gc cron jobs
 * @todo v3.3: remove
 */
class Log
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    protected $username;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $roles;

    /**
     * @ORM\Column(type="string", length=40)
     */
    protected $ip;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="request_url", type="text", nullable=true)
     */
    protected $requestUrl;

    /**
     * @ORM\Column(name="request_body", type="text", nullable=true)
     */
    protected $requestBody;

    /**
     * @ORM\Column(name="request_method", type="string", length=255)
     */
    protected $requestMethod;

    /**
     * @ORM\Column(name="response_mimetype", type="string", length=255)
     */
    protected $responseMimetype;

    /**
     * @ORM\Column(name="response_code", type="integer")
     */
    protected $responseCode;

    /**
     * @ORM\Column(name="response_size", type="integer")
     */
    protected $responseSize;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Log
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set roles
     *
     * @param string $roles
     * @return Log
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return string 
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return Log
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return Log
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set requestUrl
     *
     * @param string $requestUrl
     * @return Log
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;

        return $this;
    }

    /**
     * Get requestUrl
     *
     * @return string 
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * Set requestBody
     *
     * @param string $requestBody
     * @return Log
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    /**
     * Get requestBody
     *
     * @return string 
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * Set requestMethod
     *
     * @param string $requestMethod
     * @return Log
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }

    /**
     * Get requestMethod
     *
     * @return string 
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set responseMimetype
     *
     * @param string $responseMimetype
     * @return Log
     */
    public function setResponseMimetype($responseMimetype)
    {
        $this->responseMimetype = $responseMimetype;

        return $this;
    }

    /**
     * Get responseMimetype
     *
     * @return string 
     */
    public function getResponseMimetype()
    {
        return $this->responseMimetype;
    }

    /**
     * Set responseCode
     *
     * @param integer $responseCode
     * @return Log
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    /**
     * Get responseCode
     *
     * @return integer 
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Set responseSize
     *
     * @param integer $responseSize
     * @return Log
     */
    public function setResponseSize($responseSize)
    {
        $this->responseSize = $responseSize;

        return $this;
    }

    /**
     * Get responseSize
     *
     * @return integer 
     */
    public function getResponseSize()
    {
        return $this->responseSize;
    }

}
