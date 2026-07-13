<?php


namespace Mapbender\CoreBundle\Form\Type;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstanceLayerStyleChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
             'layer'
        ]);
        $resolver->setAllowedTypes('layer', WmsInstanceLayer::class);
        $resolver->setDefaults([
            'choices' => function(Options $options) use ($resolver): array {
                /** @var WmsInstanceLayer $layer */
                $layer = $options['layer'];
                $arrStyles = $layer->getSourceItem()->getStyles(true);
                $styleOpt = ['default' => ''];
                if (!$layer->getSublayer()->count()) {
                    foreach ($arrStyles as $style) {
                        if(strtolower((string) $style->getName()) !== 'default') {
                            $styleOpt[$style->getTitle()] = $style->getName();
                        }

                    }
                }
                return $styleOpt;
            },
        ]);
    }
}
