<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StrokeType extends AbstractType
{
    public function getName()
    {
        return 'stroke';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'color' => '#ff0000',
                'opacity' => 1,
                'width' => 1,
                'linecap' => 'round',
                'dashstyle' => 'solid'
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $linecaps = array("butt", "round", "square");
        $dashstyles = array("dot", "dash", "dashdot", "longdash", "longdashdot", "solid");
        $builder
            ->add('color', 'text', array('property_path' => '[color]'))
            ->add('opacity', 'text', array('property_path' => '[opacity]'))
            ->add('width', 'text', array('property_path' => '[width]'))
            ->add('linecap', 'choice', array(
                'choices' => array_combine($linecaps, $linecaps),
                'property_path' => '[linecap]'))
            ->add('dashstyle', 'choice', array(
                'choices' => array_combine($dashstyles, $dashstyles),
                'property_path' => '[dashstyle]'));
    }
}
