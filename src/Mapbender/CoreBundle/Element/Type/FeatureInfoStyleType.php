<?php


namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class FeatureInfoStyleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'fieldNameFillColor' => 'fillColor',
                'fieldNameStrokeColor' => 'strokeColor',
                'fieldNameOpacity' => 'opacity',
                'fieldNameStrokeWidth' => 'strokeWidth',
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($options['fieldNameFillColor'], 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.core.admin.featureinfo.label.fillColor',
                'attr' => array(
                    'class' => '-js-init-colorpicker',
                ),
            ))
            ->add($options['fieldNameStrokeColor'], 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.strokeColor',
                'attr' => array(
                    'class' => '-js-init-colorpicker',
                ),
            ))
            ->add($options['fieldNameOpacity'], 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.opacity_pct',
                'attr' => array(
                    'min' => 0,
                    'max' => 100,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 0,
                        'max' => 100,
                    )),
                ),
            ))
            ->add($options['fieldNameStrokeWidth'], 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.stroke_width_px',
                'attr' => array(
                    'min' => 0,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
        ;
    }
}
