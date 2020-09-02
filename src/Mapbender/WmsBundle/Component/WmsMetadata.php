<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Controller\ApplicationController;

/**
 * Collects template variables from a WmsInstance for MapbenderCoreBundle::metadata.html.twig
 * Renders frontend meta data for an entire Wms source or an individual layer.
 * @deprecated this entire thing should be implemented purely in twig
 * @see ApplicationController::metadataAction()
 *
 * @inheritdoc
 * @author Paul Schmidt
 */
class WmsMetadata extends SourceMetadata
{

    public function getTemplate()
    {
        return 'MapbenderCoreBundle::metadata.html.twig';
    }

    /**
     * @param SourceInstance $instance
     * @param string $itemId
     * @return array
     */
    public function getData(SourceInstance $instance, $itemId = null)
    {
        /** @var WmsInstance $instance */
        $src = $instance->getSource();
        $sectionData = array();
        $sectionData[] = $this->formatSection(static::$SECTION_COMMON, array(
            'title' => $this->formatAlternatives($src->getTitle(), $instance->getTitle()),
            'name' => strval($src->getName()),
            'version' => strval($src->getVersion()),
            'originUrl' => strval($src->getOriginUrl()),
            'description' => strval($src->getDescription()),
            'onlineResource' => strval($src->getOnlineResource()),
            'exceptionFormats' => implode(",", $src->getExceptionFormats()),
        ));

        $sectionData[] = $this->formatSection(static::$SECTION_USECONDITIONS, array(
            'fees' => strval($src->getFees()),
            'accessconstraints' => strval($src->getAccessConstraints()),
        ));

        if (($contact = $src->getContact())) {
            $sectionData[] = $this->formatSection(static::$SECTION_CONTACT, array(
                'person' => strval($contact->getPerson()),
                'position' => strval($contact->getPosition()),
                'organization' => strval($contact->getOrganization()),
                'voiceTelephone' => strval($contact->getVoiceTelephone()),
                'facsimileTelephone' => strval($contact->getFacsimileTelephone()),
                'electronicMailAddress' => strval($contact->getElectronicMailAddress()),
                'address' => strval($contact->getAddress()),
                'addressType' => strval($contact->getAddressType()),
                'addressCity' => strval($contact->getAddressCity()),
                'addressStateOrProvince' => strval($contact->getAddressStateOrProvince()),
                'addressPostCode' => strval($contact->getAddressPostCode()),
                'addressCountry' => strval($contact->getAddressCountry()),
            ));
        }

        # add items metadata
        if ($itemId) {
            foreach ($instance->getLayers() as $layer) {
                if (strval($layer->getId()) === strval($itemId)) {
                    $layerItems = $this->prepareLayers($layer);
                    $sectionData[] = $this->formatSection(static::$SECTION_ITEMS, $layerItems);
                    break;
                }
            }
        }
        return array(
            'metadata' => array(
                'sections' => $sectionData,
                'container' => $this->container ?: static::$CONTAINER_ACCORDION,
                'contenttype' => 'element',     // for legacy template compatiblity only
            ),
            'prefix' => 'mb.wms.metadata.section.',
        );
    }

    /**
     * @param WmsInstanceLayer $layer
     * @return string[][]
     */
    private function prepareLayers($layer)
    {
        $layer_items = array();
        $sourceItem = $layer->getSourceItem();
        $layer_items[] = array("name" => strval($sourceItem->getName()));
        $layer_items[] = array("title" => $this->formatAlternatives($sourceItem->getTitle(), $layer->getTitle()));
        $layer_items[] = array("abstract" =>  strval($sourceItem->getAbstract()));
        $bbox = $sourceItem->getLatlonBounds(true);
        if ($bbox) {
            $layer_items[] = array("bbox" => $this->formatBbox($bbox));
        }
        $layer_items[] = array("srs" => implode(', ', $layer->getSourceItem()->getSrs(true)));
        if($layer->getSublayer()->count() > 0){
            $sublayers = array();
            foreach($layer->getSublayer() as $sublayer){
                $sublayers[] = $this->prepareLayers($sublayer);
            }
            $layer_items[] = array(SourceMetadata::$SECTION_SUBITEMS => $sublayers);
        }
        return $layer_items;
    }

    /**
     * @param BoundingBox $bbox
     * @return string
     */
    public static function formatBbox($bbox)
    {
        return $bbox->getSrs() . " " . implode(',', array(
            $bbox->getMinx(),
            $bbox->getMiny(),
            $bbox->getMaxx(),
            $bbox->getMaxy(),
        ));
    }
}
