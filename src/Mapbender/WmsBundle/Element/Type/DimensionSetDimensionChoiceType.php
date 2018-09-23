<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DimensionSetDimensionChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'choice';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // NOTE: `use ($this)` not allowed in PHP lambda definitions...
        $resolver->setDefaults(array(
            'dimensionInsts' => array(),
            'choices' => function (Options $options) {
                $choices = array();
                foreach ($options['dimensionInsts'] as $dim) {
                    /** @var DimensionInst $dim */
                    $optionLabel = $dim->id . "-" . $dim->getName() . "-" . $dim->getType();
                    $choices[$optionLabel] = $dim;
                }
                return $choices;
            },
            'choice_value' => function (DimensionInst $inst) {
                return $inst->id;
            },
            'choice_attr' => function (DimensionInst $inst, $key, $label) {
                return array(
                    'data-config' => json_encode($inst->getConfiguration()),
                );
            },
            'choices_as_values' => true,
        ));
    }

    /**
     * @param DimensionInst[] $dimensionInsts
     * @return string[];
     */
    protected function getDimensionChoices($dimensionInsts)
    {
        $choices = array();
        foreach ($dimensionInsts as $instId => $dim) {
            $choices[$instId] = $instId . "-" . $dim->getName() . "-" . $dim->getType();
        }
        return $choices;
    }
}
