<?php


namespace Mapbender\CoreBundle\Form\Type;


use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstanceLayerStyleChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
             'layer'
        ));
        $resolver->setAllowedTypes('layer', 'Mapbender\WmsBundle\Entity\WmsInstanceLayer');
        $resolver->setDefaults(array(
            'choices' => function(Options $options) use ($resolver) {
                /** @var WmsInstanceLayer $layer */
                $layer = $options['layer'];
                $arrStyles = $layer->getSourceItem()->getStyles(true);
                $styleOpt = array(" " => "");
                foreach ($arrStyles as $style) {
                    if(strtolower($style->getName()) !== 'default'){ // accords with WMS Implementation Specification
                        $styleOpt[$style->getTitle()] = $style->getName();
                    }
                }
                return $arrStyles;
            },
        ));
        // Symfony 2 only
        if ($resolver->hasDefault('choices_as_values')) {
            $resolver->setDefault('choices_as_values', true);
        }
    }
}
