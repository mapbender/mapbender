<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtssource")
 */
class WmtsSource extends HttpTileSource
{
    /**
     * @todo: figure out how to configure Doctrine mapping to diverge
     * WmtsSource vs TmsSource classes properly vs inheritance type
     * SINGLE_TABLE in Source base class.
     *
     * (schema update + inserts + loading all have to work)
     */
    use TWmtsOnlySource;

    public function __construct()
    {
        parent::__construct();
        $this->themes = new ArrayCollection();
    }

    public function getTypeLabel()
    {
        // HACK: no distinct classes for WMTS and TMS
        if ($this->type === self::TYPE_TMS) {
            return 'TMS';
        } else {
            return 'OGC WMTS';
        }
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        parent::mutateUrls($transformer);
        if ($requestInfo = $this->getGetTile()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetTile(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetFeatureInfo()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetFeatureInfo(clone $requestInfo);
        }
    }

    public function getViewTemplate($frontend = false)
    {
        if ($frontend) {
            return null;
        } else {
            return '@MapbenderWmts/Repository/view.html.twig';
        }
    }
}
