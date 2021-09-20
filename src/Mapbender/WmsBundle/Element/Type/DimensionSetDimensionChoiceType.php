<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionSetDimensionChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // NOTE: `use ($this)` not allowed in PHP lambda definitions...
        $resolver->setDefaults(array(
            'dimensions' => array(),
            'choices' => function (Options $options) {
                return $options['dimensions'];
            },
            'choice_label' => function (DimensionInst $inst) {
                return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
            },
            'choice_value' => function (DimensionInst $inst = null) {
                if (!$inst) {
                    return null;
                } else {
                    return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
                }
            },
            'choice_attr' => function (DimensionInst $inst, $key, $label) {
                return array(
                    'data-config' => json_encode($inst->getConfiguration()),
                );
            },
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DimensionSetDimensionChoiceTransformer($options['dimensions']));
    }

}
