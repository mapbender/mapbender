<?php
namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class MetadataUrl
{
    /** @var OnlineResource|null */
    public $onlineResource;

    /** @var string|null */
    public $type;

    /**
     * Get type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * Get online resource
     *
     * @return OnlineResource|null
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set online resource
     *
     * @param OnlineResource $onlineResource
     * @return $this
     */
    public function setOnlineResource(OnlineResource $onlineResource)
    {
        $this->onlineResource = $onlineResource;
        return $this;
    }

}
