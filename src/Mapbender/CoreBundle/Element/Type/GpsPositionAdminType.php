<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GpsPositionAdminType extends AbstractType
{

    public function getParent()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('autoStart', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.element.autostart',
            ))
            ->add('average', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('follow', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->add('centerOnFirstPosition', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->add('zoomToAccuracyOnFirstPosition', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
        ;
    }
}
