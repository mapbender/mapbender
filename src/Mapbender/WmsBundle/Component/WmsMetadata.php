<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\WmsBundle\Entity\WmsInstance;

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
            $source_items[] = array("title" => $this->formatAlternatives($src->getTitle(), $this->instance->getTitle()));
            $source_items[] = array("name" => strval($src->getName()));
            $source_items[] = array("version" => strval($src->getVersion()));
            $source_items[] = array("originUrl" => strval($src->getOriginUrl()));
            $source_items[] = array("description" => strval($src->getDescription()));
            $source_items[] = array("onlineResource" => strval($src->getOnlineResource()));
            $source_items[] = array("exceptionFormats" => implode(",", $src->getExceptionFormats()));
            $this->addMetadataSection(SourceMetadata::$SECTION_COMMON, $source_items);
        }
        if ($this->getUseUseConditions()) {
            $tou_items = array();
            $tou_items[] = array("fees" => strval($src->getFees()));
            $tou_items[] = array("accessconstraints" => strval($src->getAccessConstraints()));
            $this->addMetadataSection(SourceMetadata::$SECTION_USECONDITIONS, $tou_items);
        }
        # add source contact metadata
        if ($this->getUseContact()) {
            $contact = $src->getContact();
            $contact_items = array();
            $contact_items[] = array("person" => strval($contact->getPerson()));
            $contact_items[] = array("position" => strval($contact->getPosition()));
            $contact_items[] = array("organization" => strval($contact->getOrganization()));
            $contact_items[] = array("voiceTelephone" => strval($contact->getVoiceTelephone()));
            $contact_items[] = array("facsimileTelephone" => strval($contact->getFacsimileTelephone()));
            $contact_items[] = array("electronicMailAddress" => strval($contact->getElectronicMailAddress()));
            $contact_items[] = array("address" => strval($contact->getAddress()));
            $contact_items[] = array("addressType" => strval($contact->getAddressType()));
            $contact_items[] = array("addressCity" => strval($contact->getAddressCity()));
            $contact_items[] = array("addressStateOrProvince" => strval($contact->getAddressStateOrProvince()));
            $contact_items[] = array("addressPostCode" => strval($contact->getAddressPostCode()));
            $contact_items[] = array("addressCountry" => strval($contact->getAddressCountry()));
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
        $layer_items[] = array("name" => strval($layer->getSourceItem()->getName()));
        $layer_items[] = array("title" => strval(
            $layer->getSourceItem()->getTitle()) . ' (' . $layer->getTitle() . ')');
        $bbox = $layer->getSourceItem()->getLatlonBounds();
        $layer_items[] = array("bbox" => $bbox->getSrs() . " " .
                $bbox->getMinx() . "," . $bbox->getMiny() . "," . $bbox->getMaxx() . "," . $bbox->getMaxy());
        $layer_items[] = array(
            "srs" => implode(', ', $layer->getSourceItem()->getSrs()));
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
