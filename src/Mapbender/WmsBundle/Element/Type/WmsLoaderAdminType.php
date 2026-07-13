<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Paul Schmidt
 */
class WmsLoaderAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultFormat', ChoiceType::class, [
                'label' => 'mb.core.wmsloader.admin.defaultformat',
                "choices" => [
                    "image/png" => "image/png",
                    "image/gif" => "image/gif",
                    "image/jpeg" => "image/jpeg",
                ],
            ])
            ->add('defaultInfoFormat', ChoiceType::class, [
                'label' => 'mb.core.wmsloader.admin.defaultinfoformat',
                "choices" => [
                    "text/html" => "text/html",
                    "text/xml" => "text/xml",
                    "text/plain" => "text/plain",
                ],
            ])
            ->add('autoOpen', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ])
            ->add('splitLayers', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.splitlayers',
            ])
        ;
    }

}
