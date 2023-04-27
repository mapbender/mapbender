<?php


namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class PaintType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'hasStroke' => true,
                'hasFill' => true,
                'hasFont' => false,

                'fieldNameFillColor' => 'fillColor',
                'fieldNameStrokeColor' => 'strokeColor',
                'fieldNameStrokeWidth' => 'strokeWidth',
                'fieldNameFontColor' => 'fontColor',
                'fieldNameFontSize' => 'fontSize',
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['hasStroke']) {
            $builder->add($options['fieldNameStrokeColor'], TextType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.strokeColor',
                'attr' => array(
                    'class' => '-js-init-colorpicker',
                ),
            ));
            $builder->add($options['fieldNameStrokeWidth'], IntegerType::class, array(
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
            ));
        }

        if ($options['hasFill']) {
            $builder->add($options['fieldNameFillColor'], TextType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fillColor',
                'attr' => array(
                    'class' => '-js-init-colorpicker',
                ),
            ));
        }

        if ($options['hasFont']) {
            $builder->add($options['fieldNameFontColor'], TextType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fontColor',
                'attr' => array(
                    'class' => '-js-init-colorpicker',
                ),
            ));
            $builder->add($options['fieldNameFontSize'], IntegerType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fontSize',
                'attr' => array(
                    'min' => 1,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 1,
                    )),
                ),
            ));
        }

    }
}
