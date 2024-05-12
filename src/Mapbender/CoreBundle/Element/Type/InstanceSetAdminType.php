<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstanceSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(array(
            'application',
        ));
        $resolver->setDefaults(array(
            'choice_filter' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.core.instanceset.admin.title',
            ))
            ->add('group', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.instanceset.admin.group',
            ))
            ->add('instances', 'Mapbender\CoreBundle\Form\Type\Application\SourceInstanceSelectorType', array(
                'application' => $options['application'],
                'choice_filter' => $options['choice_filter'],
                'multiple' => true,
                'label' => 'mb.core.instanceset.admin.instances',
                'required' => true,
                'label_with_layerset_prefix' => false,
            ))
        ;
    }
}
