<?php


namespace Mapbender\Component\Transformer;


interface OneWayTransformer
{
    /**
     * @param mixed $x
     * @return mixed
     */
    public function process($x);
}
