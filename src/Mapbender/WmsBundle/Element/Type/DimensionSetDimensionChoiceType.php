<?php


namespace Mapbender\WmsBundle\Element\Type;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionSetDimensionChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // NOTE: `use ($this)` not allowed in PHP lambda definitions...
        $resolver->setDefaults([
            'dimensions' => [],
            'choices' => fn(Options $options) => $options['dimensions'],
            'choice_label' => fn(DimensionInst $inst): string => $inst->id . "-" . $inst->getName() . "-" . $inst->getType(),
            'choice_value' => function (DimensionInst $inst = null): ?string {
                if (!$inst) {
                    return null;
                } else {
                    return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
                }
            },
            'choice_attr' => fn(DimensionInst $inst, $key, $label): array => [
                'data-config' => json_encode($inst->getConfiguration()),
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DimensionSetDimensionChoiceTransformer($options['dimensions']));
    }

}
