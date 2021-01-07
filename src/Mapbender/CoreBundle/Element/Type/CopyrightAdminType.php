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
        // @todo: add missing field labels
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.copyright.label.autoopen',
            ))
            // @todo: this should be a positive integer
            ->add('popupWidth', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            // @todo: this should be a positive integer
            ->add('popupHeight', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('content', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => true,
            ))
        ;
    }
}
