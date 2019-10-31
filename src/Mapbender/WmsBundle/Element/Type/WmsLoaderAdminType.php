<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Paul Schmidt
 */
class WmsLoaderAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('defaultFormat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                "choices" => array(
                    "image/png" => "image/png",
                    "image/gif" => "image/gif",
                    "image/jpeg" => "image/jpeg",
                ),
                'choices_as_values' => true,
            ))
            ->add('defaultInfoFormat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                "choices" => array(
                    "text/html" => "text/html",
                    "text/xml" => "text/xml",
                    "text/plain" => "text/plain",
                ),
                'choices_as_values' => true,
            ))
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.autoopen',
            ))
            ->add('splitLayers', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.splitlayers',
            ))
            ->add('useDeclarative', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.declarative',
            ))
        ;
    }

}
