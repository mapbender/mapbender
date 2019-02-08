<?php


namespace Mapbender\CoreBundle\Component;


class ElementCompatibilityChecker
{
    /** @var string[] */
    protected $movedElementClasses = array(
        'Mapbender\CoreBundle\Element\PrintClient' => 'Mapbender\PrintBundle\Element\PrintClient',
    );

    /**
     * @param string $classNameIn
     * @return string
     */
    public function getAdjustedElementClassName($classNameIn)
    {
        if (!empty($this->movedElementClasses[$classNameIn])) {
            $classNameOut = $this->movedElementClasses[$classNameIn];
            return $classNameOut;
        } else {
            return $classNameIn;
        }
    }
}
