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
            ->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('defaultFormat', 'choice', array(
                "choices" => array(
                    "image/png" => "image/png",
                    "image/gif" => "image/gif",
                    "image/jpeg" => "image/jpeg",
                ),
                'choices_as_values' => true,
            ))
            ->add('defaultInfoFormat', 'choice', array(
                "choices" => array(
                    "text/html" => "text/html",
                    "text/xml" => "text/xml",
                    "text/plain" => "text/plain",
                ),
                'choices_as_values' => true,
            ))
            ->add('autoOpen', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.autoopen',
            ))
            ->add('splitLayers', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.splitlayers',
            ))
            ->add('useDeclarative', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.admin.label.declarative',
            ))
        ;
    }

}
