<?php


namespace Mapbender\Component\Application;


interface TemplateAssetDependencyInterface
{
    public function getAssets($type);

    public function getLateAssets($type);
}
