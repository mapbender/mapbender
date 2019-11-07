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
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('keepSources', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => false,
                'choices' => array(
                    "no" => "no",
                    "BaseSources" => "basesources",
                    "AllSources" => "allsources",
                ),
                'choices_as_values' => true,
            ))
            ->add('components', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
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
            ->add('keepExtent', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wmc.admin.wmcloader.keep_extent',
            ))
        ;
    }

}
