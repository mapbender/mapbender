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
    public function getName()
    {
        return 'instanceset';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'instances' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
                'required' => true,
                'property_path' => '[title]'))
            ->add('group', 'text', array(
                'required' => false,
                'property_path' => '[group]'))
            ->add('instances', 'choice', array(
                'choices' => $options['instances'],
                'required' => false,
                'multiple' => true));
    }

}
