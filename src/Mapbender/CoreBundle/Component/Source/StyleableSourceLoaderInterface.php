<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\Source;

/**
 * Interface for source loaders that support loading styles for a source.
 * Implement this in any SourceLoader subclass that can fetch and persist styles.
 */
interface StyleableSourceLoaderInterface
{
    public function loadStylesForSource(Source $source): void;
}
