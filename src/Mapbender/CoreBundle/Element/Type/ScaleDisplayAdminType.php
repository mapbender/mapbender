<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\CoreBundle\Form\Type\PositionType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class ScaleDisplayAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'scaledisplay';
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
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('target', 'target_element',
                      array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
                ->add('anchor', "choice",
                      array(
                    'required' => true,
                    "choices" => array(
                        'inline' => 'inline',
                        'left-top' => 'left-top',
                        'left-bottom' => 'left-bottom',
                        'right-top' => 'right-top',
                        'right-bottom' => 'right-bottom')))
                ->add('position', new PositionType(),
                      array(
                    'label' => 'Position',
                    'property_path' => '[position]'));
    }

}