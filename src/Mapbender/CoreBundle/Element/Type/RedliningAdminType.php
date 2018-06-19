<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * RedliningAdminType class
 */
class RedliningAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'redlining';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        $builder->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('displayType', 'choice', array(
                'required' => true,
                'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
            ->add('autoActivate', 'checkbox', array('required' => false))
            ->add('deactivateOnClose', 'checkbox', array('required' => false))
            ->add('geometryTypes', 'choice', array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'point' => 'Point',
                    'line' => 'Line',
                    'polygon' => 'Polygon',
                    'rectangle' => 'Rectangle',
                    'text' => 'Text')));
    }
}
