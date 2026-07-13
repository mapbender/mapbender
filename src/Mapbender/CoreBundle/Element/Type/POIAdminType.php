<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class POIAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('useMailto', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.poi.label.usemailto',
            ])
            ->add('body', TextType::class, [
                'required' => true,
                'label' => 'mb.core.poi.admin.body',
            ])
            ->add('gps', ControlTargetType::class, [
                'required' => false,
                'label' => 'mb.core.poi.admin.gps',
                'include_buttons' => true,      // NOTE: GpsPosition is a button-type
                'element_filter_function' => fn(Element $element): bool => \is_a($element->getClass(), 'Mapbender\CoreBundle\Element\GpsPosition', true),
            ])
        ;
    }
}
