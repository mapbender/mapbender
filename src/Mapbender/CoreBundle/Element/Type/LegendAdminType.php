<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LegendAdminType extends AbstractType
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
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('elementType', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    "dialog" => "dialog",
                    "blockelement" => "blockelement",
                ),
                'choices_as_values' => true,
            ))
            ->add('autoOpen', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.autoopen',
            ))
            ->add('displayType', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    "list" => "list",
                ),
                'choices_as_values' => true,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('showSourceTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showsourcetitle',
            ))
            ->add('showLayerTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showlayertitle',
            ))
            ->add('showGroupedLayerTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showgroupedlayertitle',
            ))
        ;
    }

}
