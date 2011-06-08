<?php

namespace MB\WMSBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class WMSLayerType  extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options){
        $builder->add("title");
        $builder->add("name","text",array(
        ));
        $builder->add("abstract","text",array(
            "required" => false,
        ));
        $builder->add("layer",'collection',array( 'type' => new WMSLayerType(),));

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "MB\WMSBundle\Entity\WMSLayer"
        );
   }
}
