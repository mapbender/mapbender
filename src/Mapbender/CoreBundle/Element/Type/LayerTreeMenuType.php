<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayerTreeMenuType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = $this->getChoices();
        $resolver->setDefaults(array(
            'choices' => $choices,
            'multiple' => true,
            'attr' => array(
                'size' => count($choices),
            ),
        ));
    }

    public function getParent(): string
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    /**
     * @return string[]
     */
    public function getChoices(): array
    {
        return array(
            'mb.core.layertree.admin.layerremove' => 'layerremove',
            'mb.core.layertree.admin.opacity' => 'opacity',
            'mb.core.layertree.admin.zoomtolayer' => 'zoomtolayer',
            'mb.core.layertree.admin.metadata' => 'metadata',
            'mb.core.layertree.admin.dimension' => 'dimension',
            'mb.core.layertree.admin.select_style' => 'select_style',
        );
    }
}
