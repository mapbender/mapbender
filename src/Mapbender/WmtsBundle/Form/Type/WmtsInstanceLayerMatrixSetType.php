<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WmtsInstanceLayerMatrixSetType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $self = $this;

        $resolver->setDefaults(array(
            'instance_layer' => null,
            'choices' => function(Options $options) use ($self) {
                return $self->getChoices($options['instance_layer']);
            },
            'choices_as_values' => true,
        ));
    }

    public static function getChoices(WmtsInstanceLayer $instanceLayer)
    {
        $matrixSets = $instanceLayer->getSourceInstance()->getSource()->getTilematrixsets();
        $tileMatrixLinks = $instanceLayer->getSourceItem()->getTilematrixSetlinks();
        $choices = array();
        foreach ($tileMatrixLinks as $tileMatrixLink) {
            foreach ($matrixSets as $tilematrixset) {
                if ($tilematrixset->getIdentifier() === $tileMatrixLink->getTileMatrixSet()) {
                    $choices[$tilematrixset->getIdentifier()] = $tilematrixset->getIdentifier();
                }
            }
        }
        return $choices;
    }
}
