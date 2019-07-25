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
    public function getName()
    {
        return 'wmcloader';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
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
                'property_path' => '[target]',
                'required' => false,
            ))
            ->add('keepSources', 'choice', array(
                'required' => false,
                'choices' => array(
                    "no" => " no ",
                    "basesources" => "BaseSources",
                    "allsources" => "AllSources",
                ),
            ))
            ->add('components', 'choice', array(
                'multiple' => true,
                'required' => true,
                'preferred_choices' => array("loader"),
                'choices' => array(
                    "wmcidloader" => "Id Loader",
                    "wmclistloader" => "From List Loader",
                    "wmcxmlloader" => "Wmc Xml Loader",
                    "wmcurlloader" => "Wmc From Url Loader",
                ),
            ))
            ->add('keepExtent', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wmc.admin.wmcloader.keep_extent',
            ))
        ;
    }

}
