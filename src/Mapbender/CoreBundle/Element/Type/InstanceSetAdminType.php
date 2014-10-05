<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'layerset' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text',
                array(
                'required' => true,
                'property_path' => '[title]'))
            ->add('group', 'text',
                array(
                'required' => false,
                'property_path' => '[group]'))
            ->add('instances', 'layerset_instances',
                array(
                'layerset' => $options['layerset'],
                'property_path' => '[instances]',
                'required' => false,
                'multiple' => true));
    }

}
