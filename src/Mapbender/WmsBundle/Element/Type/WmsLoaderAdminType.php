<?php

namespace Mapbender\WmsBundle\Element\Type;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('defaultFormat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                "choices" => array(
                    "image/png" => "image/png",
                    "image/gif" => "image/gif",
                    "image/jpeg" => "image/jpeg",
                ),
            ))
            ->add('defaultInfoFormat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                "choices" => array(
                    "text/html" => "text/html",
                    "text/xml" => "text/xml",
                    "text/plain" => "text/plain",
                ),
            ))
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ))
            ->add('splitLayers', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.splitlayers',
            ))
        ;
    }

}
