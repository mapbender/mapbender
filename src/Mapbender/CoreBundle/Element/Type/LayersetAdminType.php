<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Form\Type\OrderAwareMultipleChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayersetAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getParent(): string
    {
        return OrderAwareMultipleChoiceType::class;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'application' => null,
            'choices' => function (Options $options): array {
                /** @var Application $application */
                $application = $options['application'];
                $choices = [];
                foreach ($application->getLayersets() as $layerset) {
                    $choices[$layerset->getTitle()] = $layerset->getId();
                }
                return $choices;
            },
        ]);
    }
}
