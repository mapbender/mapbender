<?php

namespace MB\WMSBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * @package Mapbender
 * @author Karim Malhas <karim@malhas.de>
*/
class WMSType  extends AbstractType {

    public function getName (){ return "WMSService";}

    public function buildForm(FormBuilder $builder, array $options){
        $builder->add("version","text", array(
            "required"  => false,
        ));

        // Service Section Elements
        $builder->add("name","text",array(
            "required" => false,
        ));
        $builder->add("title","text", array(
            "required"  => false,
        ));
        $builder->add("abstract","text",array(
            "required" => false,
        ));
        $builder->add("onlineResource","text", array(
            "required" => false,
        ));
        $builder->add("ContactPerson", "text",array(
            "required" => false,
        ));
        $builder->add("ContactOrganization", "text",array(
            "required" => false,
        ));
        $builder->add("ContactPosition", "text",array(
            "required" => false,
        ));
        $builder->add("ContactVoiceTelephone", "text",array(
            "required" => false,
        ));
        $builder->add("ContactFacsimileTelephone", "text",array(
            "required" => false,
        ));
        $builder->add("ContactElectronicMailAddress", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddress", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddressType", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddressCity", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddressStateOrProvince", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddressPostCode", "text",array(
            "required" => false,
        ));
        $builder->add("ContactAddressCountry", "text",array(
            "required" => false,
        ));
        $builder->add("fees","text",array(
            "required" => false,
        ));
        $builder->add("accessConstraints","text",array(
            "required" => false,
        ));

        // Capabilites > Request Section Elements
        $builder->add("requestGetCapabilitiesGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetCapabilitiesPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetCapabilitiesFormats","text",array(
            "required" => false,
        ));

        $builder->add("requestGetMapGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetMapPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetMapFormats","text",array(
            "required" => false,
        ));

        $builder->add("requestGetFeatureInfoGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetFeatureInfoPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetFeatureInfoFormats","text",array(
            "required" => false,
        ));
        
        $builder->add("requestDescribeLayerGET","text",array(
            "required" => false,
        ));
        $builder->add("requestDescribeLayerPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestDescribeLayerFormats","text",array(
            "required" => false,
        ));
        
        $builder->add("requestGetLegendGraphicGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetLegendGraphicPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetLegendGraphicFormats","text",array(
            "required" => false,
        ));
        
        $builder->add("requestGetStylesGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetStylesPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetStylesFormats","text",array(
            "required" => false,
        ));
        
        $builder->add("requestPutStylesGET","text",array(
            "required" => false,
        ));
        $builder->add("requestPutStylesPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestPutStylesFormats","text",array(
            "required" => false,
        ));

        $builder->add("exceptionFormats","text",array(
            "required"  => false,
        ));

        $builder->add("symbolSupportSLD","boolean",array(
            "required"  => false,
        ));
        $builder->add("symbolUserLayer","boolean",array(
            "required"  => false,
        ));
        $builder->add("symbolUserStyle","boolean",array(
            "required"  => false,
        ));
        $builder->add("symbolRemoteWFS","boolean",array(
            "required"  => false,
        ));

        $builder->add("layer",'collection',array( 
            'type' => new WMSLayerType(),
        ));

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "MB\WMSBundle\Entity\WMSService"
        );
   }
}
