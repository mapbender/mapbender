<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Style;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;

class OgcApiFeaturesInstanceLayerType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
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
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.title',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('minScale', NumberType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.min_scale',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('maxScale', NumberType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.max_scale',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('featureLimit', IntegerType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.feature_limit',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('allowSelected', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('allowInfo', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('info', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('priority', HiddenType::class, array(
                'required' => true,
            ))
            ->add('styleId', ChoiceType::class, [
                'required' => false,
                'label' => false,
                'placeholder' => '-- none --',
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
            ])
        ;
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
