<?php
namespace Mapbender\CoreBundle\Component;

/**
 * @author Paul Schmidt
 */
class StateHandler
{
    private ?Size $window = null;
    private ?BoundingBox $extent = null;
    private ?BoundingBox $maxextent = null;
    /** @var array[] */
    private $sources = [];
    
    /**
     * Sets window
     * 
     * @param Size $value
     * @return $this
     */
    public function setWindow(Size $value): static{
        $this->window = $value;
        return $this;
    }
    
    /**
     * Returns window
     * 
     * @return Size
     */
    public function getWindow(){
        return $this->window;
    }
    
    
    
    /**
     * Sets extent
     * 
     * @param BoundingBox $value
     * @return $this
     */
    public function setExtent(BoundingBox $value): static{
        $this->extent = $value;
        return $this;
    }
    
    /**
     * Returns extent
     * 
     * @return BoundingBox
     */
    public function getExtent(){
        return $this->extent;
    }
    
    
    
    /**
     * Sets maxextent
     * 
     * @param BoundingBox $value
     * @return $this
     */
    public function setMaxextent(BoundingBox $value): static{
        $this->maxextent = $value;
        return $this;
    }
    
    /**
     * Returns maxextent
     * 
     * @return BoundingBox
     */
    public function getMaxextent(){
        return $this->maxextent;
    }
    
    /**
     * Sets sources
     * 
     * @param array[] $value
     * @return $this
     */
    public function setSources($value): static{
        $this->sources = $value;
        return $this;
    }
    
    /**
     * Returns sources
     * 
     * @return array
     */
    public function getSources(){
        return $this->sources;
    }
    
    /**
     * Adds source
     *
     * @param array $value
     * @return $this
     */
    public function addSource($value): static
    {
        $this->sources[] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $windowArr = $this->window->toArray();
        $extentArr = $this->extent->toArray();
        $maxExtentArr = $this->maxextent === null ? $this->extent->toArray() : $this->maxextent->toArray();
        $sourcesArr = $this->sources;
        return [
            "window" => $windowArr,
            "extent" => $extentArr,
            "maxextent" => $maxExtentArr,
            "sources" => $sourcesArr,
        ];
    }
}
