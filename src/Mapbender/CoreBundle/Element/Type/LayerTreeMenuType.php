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
            'mb.core.layertree.admin.layerremove' => 'layerremove',
            'mb.core.layertree.admin.opacity' => 'opacity',
            'mb.core.layertree.admin.zoomtolayer' => 'zoomtolayer',
            'mb.core.layertree.admin.metadata' => 'metadata',
            'mb.core.layertree.admin.dimension' => 'dimension',
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
