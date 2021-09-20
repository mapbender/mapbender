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
                $styleOpt = array('default' => '');
                foreach ($arrStyles as $style) {
                    if(strtolower($style->getName()) !== 'default') {
                        $styleOpt[$style->getTitle()] = $style->getName();
                    }

                }
                return $styleOpt;
            },
        ));
    }
}
