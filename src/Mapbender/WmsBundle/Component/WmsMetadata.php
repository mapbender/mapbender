<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * 
 * @inheritdoc
 * @author Paul Schmidt
 */
class WmsMetadata extends SourceMetadata
{

    protected $instance;

    public function __construct(WmsInstance $instance)
    {
        parent::__construct();
        $this->instance = $instance;
    }

    private function prepareData($itemName)
    {
        $src = $this->instance->getSource();
        if ($this->getUseCommon()) {
            $source_items = array();
            $source_items[] = array("title" => SourceMetadata::getNotNull($src->getTitle(), $this->instance->getTitle()));
            $source_items[] = array("name" => SourceMetadata::getNotNull($src->getName()));
            $source_items[] = array("version" => SourceMetadata::getNotNull($src->getVersion()));
            $source_items[] = array("originUrl" => SourceMetadata::getNotNull($src->getOriginUrl()));
            $source_items[] = array("description" => SourceMetadata::getNotNull($src->getDescription()));
            $source_items[] = array("onlineResource" =>
                SourceMetadata::getNotNull($src->getOnlineResource() !== null ? $src->getOnlineResource() : ""));
            $source_items[] = array("exceptionFormats" => SourceMetadata::getNotNull(implode(",", $src->getExceptionFormats())));
            $this->addMetadataSection(SourceMetadata::$SECTION_COMMON, $source_items);
        }
        if ($this->getUseUseConditions()) {
            $tou_items = array();
            $tou_items[] = array("fees" => SourceMetadata::getNotNull($src->getFees()));
            $tou_items[] = array("accessconstraints" => SourceMetadata::getNotNull($src->getAccessConstraints()));
            $this->addMetadataSection(SourceMetadata::$SECTION_USECONDITIONS, $tou_items);
        }
        # add source contact metadata
        if ($this->getUseContact()) {
            $contact = $src->getContact();
            $contact_items = array();
            $contact_items[] = array("person" => SourceMetadata::getNotNull($contact->getPerson()));
            $contact_items[] = array("position" => SourceMetadata::getNotNull($contact->getPosition()));
            $contact_items[] = array("organization" => SourceMetadata::getNotNull($contact->getOrganization()));
            $contact_items[] = array("voiceTelephone" => SourceMetadata::getNotNull($contact->getVoiceTelephone()));
            $contact_items[] = array("facsimileTelephone" => SourceMetadata::getNotNull($contact->getFacsimileTelephone()));
            $contact_items[] = array("electronicMailAddress" => SourceMetadata::getNotNull($contact->getElectronicMailAddress()));
            $contact_items[] = array("address" => SourceMetadata::getNotNull($contact->getAddress()));
            $contact_items[] = array("addressType" => SourceMetadata::getNotNull($contact->getAddressType()));
            $contact_items[] = array("addressCity" => SourceMetadata::getNotNull($contact->getAddressCity()));
            $contact_items[] = array("addressStateOrProvince" => SourceMetadata::getNotNull($contact->getAddressStateOrProvince()));
            $contact_items[] = array("addressPostCode" => SourceMetadata::getNotNull($contact->getAddressPostCode()));
            $contact_items[] = array("addressCountry" => SourceMetadata::getNotNull($contact->getAddressCountry()));
            $this->addMetadataSection(SourceMetadata::$SECTION_CONTACT, $contact_items);
        }

        # add items metadata
        if ($this->getUseItems() && $itemName !== '') {
            $layer = null;
            foreach ($this->instance->getLayers() as $layerH) {
                if ($layerH->getSourceItem()->getName() === $itemName) {
                    $layer = $layerH;
                    break;
                }
            }
            $layer_items = array();
            if ($layer) {
                $layer_items = $this->prepareLayers($layer);
            }
            $this->addMetadataSection(SourceMetadata::$SECTION_ITEMS, $layer_items);
        }
    }
    
    private function prepareLayers($layer)
    {
        $layer_items = array();
        $layer_items[] = array("name" => SourceMetadata::getNotNull($layer->getSourceItem()->getName()));
        $layer_items[] = array("title" => SourceMetadata::getNotNull(
            $layer->getSourceItem()->getTitle()) . ' (' . $layer->getTitle() . ')');
        $bbox = $layer->getSourceItem()->getLatlonBounds();
        $layer_items[] = array("bbox" => SourceMetadata::getNotNull($bbox->getSrs() . " " .
                $bbox->getMinx() . "," . $bbox->getMiny() . "," . $bbox->getMaxx() . "," . $bbox->getMaxy()));
        $layer_items[] = array(
            "srs" => SourceMetadata::getNotNull(implode(', ', $layer->getSourceItem()->getSrs())));
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
     * @inheritdoc
     */
    public function render($templating, $itemName = null)
    {
        $this->prepareData($itemName);
        $content = $templating->render('MapbenderCoreBundle::metadata.html.twig',
            array('metadata' => $this->data, 'prefix' => 'mb.wms.metadata.section.'));
        return $content;
    }

}
