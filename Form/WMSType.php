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
        $builder->add("requestGetCapabilitiesFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestGetCapabilitiesFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));

        $builder->add("requestGetMapGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetMapPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetMapFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestGetMapFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));

        $builder->add("requestGetFeatureInfoGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetFeatureInfoPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetFeatureInfoFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestGetFeatureInfoFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));
        
        $builder->add("requestDescribeLayerGET","text",array(
            "required" => false,
        ));
        $builder->add("requestDescribeLayerPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestDescribeLayerFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestDescribeLayerFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));
        
        $builder->add("requestGetLegendGraphicGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetLegendGraphicPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetLegendGraphicFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestGetLegendGraphicFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));
        
        $builder->add("requestGetStylesGET","text",array(
            "required" => false,
        ));
        $builder->add("requestGetStylesPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestGetStylesFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestGetStylesFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));
        
        $builder->add("requestPutStylesGET","text",array(
            "required" => false,
        ));
        $builder->add("requestPutStylesPOST","text",array(
            "required" => false,
        ));
        $builder->add("requestPutStylesFormats","choice",array(
            "required" => false,
            "choices"   => $options['requestPutStylesFormats'],
            "multiple"  => true,
            "expanded"  => true,
        ));

        $exceptionFormatChoices = array_combine(
            $options['exceptionFormats'],
            $options['exceptionFormats']
        );
        
        // Symfony2 is silly an does not work if a value contains a dot
        $builder->add("exceptionFormats","choice",array(
            "required"  => false,
            "choices"   => $exceptionFormatChoices,
            "multiple"  => true,
            "expanded"  => true
        ));

        $builder->add("symbolSupportSLD","checkbox",array(
            "required"  => false,
        ));
        $builder->add("symbolUserLayer","checkbox",array(
            "required"  => false,
        ));
        $builder->add("symbolUserStyle","checkbox",array(
            "required"  => false,
        ));
        $builder->add("symbolRemoteWFS","checkbox",array(
            "required"  => false,
        ));

        $builder->add("layer",'collection',array( 
            'type' => new WMSLayerType(),
        ));

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "MB\WMSBundle\Entity\WMSService",
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
