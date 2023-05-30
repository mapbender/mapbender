<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayerTreeMenuType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = array(
            'Remove layer' => 'layerremove',
            'Opacity' => 'opacity',
            'Zoom to layer' => 'zoomtolayer',
            'Metadata' => 'metadata',
            'Dimension' => 'dimension',
        );
        $resolver->setDefaults(array(
            'choices' => $choices,
            'multiple' => true,
            'attr' => array(
                'size' => count($choices),
            ),
        ));
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }
}
