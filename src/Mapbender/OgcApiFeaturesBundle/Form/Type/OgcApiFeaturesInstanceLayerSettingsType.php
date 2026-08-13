<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\CoreBundle\Element\Type\PaintType;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Form\Transformer\JsonArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class OgcApiFeaturesInstanceLayerSettingsType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OgcApiFeaturesInstanceLayer::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $styleChoices = $this->getStyleChoices();

        $builder
            ->add('tooltipPropertyMap', HiddenType::class, [
                'required' => false,
            ])
            ->add('tooltipTemplate', TextareaType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.tooltip.template',
                'help' => 'mb.ogcapifeatures.admin.tooltip.template_help',
            ], $this->translator))
            ->add('featureInfoPropertyMap', HiddenType::class, [
                'required' => false,
            ])
            ->add('featureInfoTemplate', TextareaType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.featureinfo.template',
                'help' => 'mb.ogcapifeatures.admin.featureinfo.template_help',
            ], $this->translator))
            ->add('hoverStyle',PaintType::class, $this->createInlineHelpText([
               'label' => 'mb.ogcapifeatures.admin.tooltip.style',
               'help' => 'mb.ogcapifeatures.admin.tooltip.style_help',
            ], $this->translator))
            ->add('styleId', ChoiceType::class, [
                'required' => false,
                'label' => false,
                'placeholder' => 'mb.manager.admin.style.none',
                'choices' => $styleChoices,
                'attr' => ['class' => 'form-select form-select-sm style-select'],
            ])
            ->add('nativeStyleId', HiddenType::class, [
                'required' => false,
            ])
            ->add('secondaryStyleIds', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'label' => false,
                'choices' => $styleChoices,
                'attr' => ['class' => 'form-select form-select-sm secondary-style-select', 'size' => 6],
            ]);

        $builder->get('tooltipPropertyMap')->addModelTransformer(new JsonArrayTransformer());
    }

    private function getStyleChoices(): array
    {
        $styles = $this->em->getRepository(Style::class)->findAll();
        $choices = [];
        foreach ($styles as $style) {
            $label = $style->getName() ?: 'Style #' . $style->getId();
            $choices[$label] = $style->getId();
        }
        return $choices;
    }
}
