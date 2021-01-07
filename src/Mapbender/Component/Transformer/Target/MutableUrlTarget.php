<?php


namespace Mapbender\Component\Transformer\Target;


use Mapbender\Component\Transformer\OneWayTransformer;

interface MutableUrlTarget
{
    public function mutateUrls(OneWayTransformer $transformer);
}
