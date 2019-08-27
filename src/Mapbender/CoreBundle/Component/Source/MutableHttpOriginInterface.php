<?php


namespace Mapbender\CoreBundle\Component\Source;


interface MutableHttpOriginInterface extends HttpOriginInterface
{
    /**
     * @param string $originUrl
     * @return $this
     */
    public function setOriginUrl($originUrl);

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password);

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username);
}
