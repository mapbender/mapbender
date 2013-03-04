<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class ScaleSelectorAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'scaleselector';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
//            'target' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('label', 'checkbox', array('required' => false))
                ->add('target', 'target_element',
                      array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false));
    }

}