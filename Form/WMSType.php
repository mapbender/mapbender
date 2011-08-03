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
        $builder->add("getMapGet","hidden",array(
            "required" => false,
        ));
        $builder->add("getMapFormats","hidden",array(
            "required" => false,
        ));
        $builder->add("getMapFormats","hidden",array(
            "required" => false,
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
