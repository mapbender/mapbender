<?php
namespace Mapbender\WmcBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Paul Schmidt
 */
class WmcListAdminType extends AbstractType
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
                'element_class' => 'Mapbender\\WmcBundle\\Element\\WmcLoader',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
            ->add('label', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wmc.admin.wmclist.label',
            ))
        ;
    }
}
