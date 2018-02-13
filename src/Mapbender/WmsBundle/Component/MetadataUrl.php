<?php
namespace Mapbender\WmsBundle\Component;

/**
 * MetadataUrl class.
 *
 * @author Paul Schmidt
 */
class MetadataUrl
{
    /**
     * ORM\Column(type="object", nullable=true)
     */
    public $onlineResource;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $type;

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $value
     * @return MetadataUrl
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * Get online resource
     *
     * @return OnlineResource
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set online resource
     *
     * @param OnlineResource $onlineResource
     * @return MetadataUrl
     */
    public function setOnlineResource(OnlineResource $onlineResource)
    {
        $this->onlineResource = $onlineResource;
        return $this;
    }

}
