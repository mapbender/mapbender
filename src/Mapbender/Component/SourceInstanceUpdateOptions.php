<?php


namespace Mapbender\Component;

/**
 * POD settings carrier used when a source instance needs to
 * be changed / extended on source reload.
 */
class SourceInstanceUpdateOptions
{
    public $newLayersActive = true;
    public $newLayersSelected = true;
}
