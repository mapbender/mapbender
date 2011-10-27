<?php

namespace Mapbender\WmsBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * @package Mapbender
 * @author Karim Malhas <karim@malhas.de>
*/
class WMSType  extends AbstractType {

    public function getName (){ return "WMSService";}


    /**
     * converts an array ["a"] into ["a" =>"a"]
    */
    protected function makeFormatChoices($array){
        $keys = $array;
        $values = array(); 

        // See also: CapabilitiesParser where Formats is parsed
        foreach ($array as $entry){
            $values[] = str_replace("__",".",$entry);
        }
        return count($array) ? array_combine($keys,$values): array();
    }

    public function buildForm(FormBuilder $builder, array $options){



        $builder->add("version","text", array(
            "required"  => false,
            "label"     => "Version",
        ));

        // Service Section Elements
        $builder->add("name","text",array(
            "required" => false,
            "label"     => "Name",
        ));
        $builder->add("title","text", array(
            "required"  => false,
            "label"     => "Title",
        ));
        $builder->add("alias","text", array(
            "required"  => false,
            "label"     => "Alias",
        ));
        $builder->add("abstract","text",array(
            "required" => false,
        ));
        $builder->add("username","text", array(
            "required"  => false,
            "label"     => "Username",
        ));
        $builder->add("password","text", array(
            "required"  => false,
        ));
        $builder->add("onlineResource","text", array(
            "required" => false,
            "label"     => "OnlineResource",
        ));
        $builder->add("contactPerson", "text",array(
            "required" => false,
            "label"     => "Person",
        ));
        $builder->add("contactOrganization", "text",array(
            "required" => false,
            "label"     => "Organization",
        ));
        $builder->add("contactPosition", "text",array(
            "required" => false,
            "label"     => "Position",
        ));
        $builder->add("contactVoiceTelephone", "text",array(
            "required" => false,
            "label"     => "Telephone",
        ));
        $builder->add("contactFacsimileTelephone", "text",array(
            "required" => false,
            "label"     => "Facsimile",
        ));
        $builder->add("contactElectronicMailAddress", "text",array(
            "required" => false,
            "label"     => "Email",
        ));
        $builder->add("contactAddress", "text",array(
            "required" => false,
            "label"     => "Address",
        ));
        $builder->add("contactAddressType", "text",array(
            "required" => false,
            "label"     => "Addresstype",
        ));
        $builder->add("contactAddressCity", "text",array(
            "required" => false,
            "label"     => "City",
        ));
        $builder->add("contactAddressStateOrProvince", "text",array(
            "required" => false,
            "label"     => "State or Province",
        ));
        $builder->add("contactAddressPostCode", "text",array(
            "required" => false,
            "label"     => "Postcode",
        ));
        $builder->add("contactAddressCountry", "text",array(
            "required" => false,
            "label"     => "Country",
        ));
        $builder->add("fees","textarea",array(
            "required" => false,
            "label"     => "Fees",
        ));
        $builder->add("accessConstraints","textarea",array(
            "required" => false,
            "label"     => "Access constrains",
        ));

        // Capabilites > Request Section Elements
        $builder->add("requestGetCapabilitiesGET","text",array(
            "required" => false,
            "label"     => "GetCapabilities GET URL",
        ));
        $builder->add("requestGetCapabilitiesPOST","text",array(
            "required" => false,
            "label"     => "GetCapabilities POST URL",
        ));
        $builder->add("requestGetCapabilitiesFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestGetCapabilitiesFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "GetCapabilties Formats",
        ));

        $builder->add("requestGetMapGET","text",array(
            "required" => false,
            "label"     => "getMap GET URL",
        ));
        $builder->add("requestGetMapPOST","text",array(
            "required" => false,
            "label"     => "getMap Post URL",
        ));
        $builder->add("requestGetMapFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestGetMapFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "getMap Formats",
        ));

        $builder->add("requestGetFeatureInfoGET","text",array(
            "required" => false,
            "label"     => "GetFeatureInfo GET URL",
        ));
        $builder->add("requestGetFeatureInfoPOST","text",array(
            "required" => false,
            "label"     => "GetFeatureInfo Post URL",
        ));
        $builder->add("requestGetFeatureInfoFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestGetFeatureInfoFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "GetFeatureInfo Formats",
        ));
        
        $builder->add("requestDescribeLayerGET","text",array(
            "required" => false,
            "label"     => "DescribeLayer GET URL",
        ));
        $builder->add("requestDescribeLayerPOST","text",array(
            "required" => false,
            "label"     => "DescribeLayer POST URL",
        ));

        $builder->add("requestDescribeLayerFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestDescribeLayerFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "DescribeLayer Formats",
        ));
        
        $builder->add("requestGetLegendGraphicGET","text",array(
            "required" => false,
            "label"     => "GetLegendGraphic GET URL",
        ));
        $builder->add("requestGetLegendGraphicPOST","text",array(
            "required" => false,
            "label"     => "GetLegendGraphic POST URL",
        ));
        
        $builder->add("requestGetLegendGraphicFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices( $options['requestGetLegendGraphicFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "GetLegendGraphic Formats",
        ));
        
        $builder->add("requestGetStylesGET","text",array(
            "required" => false,
            "label"     => "GetStyles GET URL",
        ));
        $builder->add("requestGetStylesPOST","text",array(
            "required" => false,
            "label"     => "GetStyles POST URL",
        ));

        $builder->add("requestGetStylesFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestGetStylesFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "GetStyles Formats",
        ));
        
        $builder->add("requestPutStylesGET","text",array(
            "required" => false,
            "label"     => "PutStyles GET URL",
        ));
        $builder->add("requestPutStylesPOST","text",array(
            "required" => false,
            "label"     => "PutStyles POST URL",
        ));
        $builder->add("requestPutStylesFormats","choice",array(
            "required" => false,
            "choices"   => $this->makeFormatChoices($options['requestPutStylesFormats']),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "PutStyles Formats",
        ));

        
        $builder->add("exceptionFormats","choice",array(
            "required"  => false,
            "choices"   => $this->makeFormatChoices($options["exceptionFormats"]),
            "multiple"  => true,
            "expanded"  => true,
            "label"     => "Exception Formats"
        ));

        $builder->add("symbolSupportSLD","hidden",array(
        ));
        $builder->add("symbolUserLayer","hidden",array(
        ));
        $builder->add("symbolUserStyle","hidden",array(
        ));
        $builder->add("symbolRemoteWFS","hidden",array(
        ));

        $builder->add("layer",'collection',array( 
            'type' => new WMSLayerType(),
        ));

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "Mapbender\WmsBundle\Entity\WMSService",
            "exceptionFormats" => array(),
            "requestGetCapabilitiesFormats" => array(),
            "requestGetMapFormats" => array(),
            "requestGetFeatureInfoFormats" => array(),
            "requestDescribeLayerFormats"  => array(),
            "requestGetLegendGraphicFormats" => array(),
            "requestGetStylesFormats" => array(),
            "requestPutStylesFormats" => array(),
        );
   }
}
