<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GpsPositionAdminType extends AbstractType
{

    public function getParent(): string
    {
        return BaseButtonAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoStart', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.element.autostart',
            ])
            ->add('average', TextType::class, [
                'required' => false,
                'label' => 'mb.core.gpsposition.admin.average',
            ])
            ->add('follow', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.gpsposition.admin.follow',
            ])
            ->add('centerOnFirstPosition', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.gpsposition.admin.centeronfirstposition',
            ])
            ->add('zoomToAccuracyOnFirstPosition', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.gpsposition.admin.zoomtoaccuracyonfirstposition',
            ])
        ;
    }
}
