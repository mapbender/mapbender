<?php

namespace Mapbender\WmsBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class WMSLayerType  extends AbstractType {

    public function getName (){ return "WMSLayer";}
    public function buildForm(FormBuilder $builder, array $options){
        $builder->add("title","text",array(
            "required" => false,
        ));
        $builder->add("name","text",array(
            "required" => false,
        ));
        $builder->add("abstract","text",array(
            "required" => false,
        ));
        $builder->add("metadataurl","text",array(
            "required" => false,
        ));
        $builder->add("dataurl","text",array(
            "required" => false,
        ));
        $builder->add("srs","hidden",array(
            "required" => false,
        ));
        $builder->add("latLonBounds","hidden",array(
            "required" => false,
        ));
        $builder->add("queryable","checkbox",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("cascaded","integer",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("opaque","checkbox",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("noSubset","checkbox",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("fixedWidth","integer",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("fixedHeight","integer",array(
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("scaleHintMin","number",array(
            "precision" => "20",
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("scaleHintMax","number",array(
            "precision" => "20",
            "required"  => false,
            "read_only"  => true,
        ));
        $builder->add("layer",'collection',array( 
            'type' => new WMSLayerType(),
        ));

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "Mapbender\WmsBundle\Entity\WMSLayer"
        );
   }
}
