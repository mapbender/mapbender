<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CopyrightAdminType extends AbstractType
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
            ->add('autoOpen', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.copyright.label.autoopen',
            ))
            ->add('popupWidth', 'text', array('required' => true))
            ->add('popupHeight', 'text', array('required' => true))
            ->add('content', 'textarea', array('required' => true))
        ;
    }

}