<?php
namespace Mapbender\WmcBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Paul Schmidt
 */
class WmcLoaderAdminType extends AbstractType
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
            ->add('keepSources', 'choice', array(
                'required' => false,
                'choices' => array(
                    "no" => "no",
                    "BaseSources" => "basesources",
                    "AllSources" => "allsources",
                ),
                'choices_as_values' => true,
            ))
            ->add('components', 'choice', array(
                'multiple' => true,
                'required' => true,
                'choices' => array(
                    "Id Loader" => "wmcidloader",
                    "From List Loader" => "wmclistloader",
                    "Wmc Xml Loader" => "wmcxmlloader",
                    "Wmc From Url Loader" => "wmcurlloader",
                ),
                'choices_as_values' => true,
            ))
            ->add('keepExtent', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wmc.admin.wmcloader.keep_extent',
            ))
        ;
    }

}
