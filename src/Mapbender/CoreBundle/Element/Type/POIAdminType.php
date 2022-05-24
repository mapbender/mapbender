<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class POIAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('useMailto', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.poi.label.usemailto',
            ))
            ->add('body', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('gps', 'Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType', array(
                'required' => false,
                'include_buttons' => true,      // NOTE: GpsPosition is a button-type
                'element_filter_function' => function(Element $element) {
                    return \is_a($element->getClass(), 'Mapbender\CoreBundle\Element\GpsPosition', true);
                },
            ))
        ;
    }
}
