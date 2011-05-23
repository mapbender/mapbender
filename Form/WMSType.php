<?php

namespace MB\WMSBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;


class WMSType  extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options){

    }
    public function getDefaultOptions(array $options){
        return array(
            'data_class' => "MB\WMSBundle\Entity\WMS"
        );
   }
}
