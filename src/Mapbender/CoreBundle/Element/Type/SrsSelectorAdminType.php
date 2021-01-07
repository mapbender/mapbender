<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SrsSelectorAdminType extends AbstractType
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
            ->add('label', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.srsselector.show_label',
            ))
            ->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
        ;
    }

}
