<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
//use Symfony\Component\Form\FormView;
//use Symfony\Component\Form\FormInterface;

class FillType extends AbstractType
{
    public function getName()
    {
        return 'fill';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'color' => '#ee9900',
                'opacity' => 0.4
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('color', 'text', array())
            ->add('opacity', 'text', array());
    }
}
