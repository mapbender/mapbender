<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;

class OgcApiFeaturesInstanceType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getParent(): ?string
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('opacity', IntegerType::class, [
                'required' => false,
                'label' => 'mb.manager.source.option.opacity',
            ])
            ->add('minScale', NumberType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.min_scale',
            ])
            ->add('maxScale', NumberType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.max_scale',
            ])
            ->add('featureLimit', IntegerType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.feature_limit',
            ])
            ->add('allowSelected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.allow_selected',
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.selected',
            ])
            ->add('allowToggle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.allow_toggle',
            ])
            ->add('toggle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.toggle',
            ])
            ->add('featureInfoPropertyMap', YAMLConfigurationType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.property_map',
                'help' => 'mb.vectortiles.admin.featureinfo.property_map_help',
                'json_encode' => true,
            ], $this->translator))
            ->add('featureInfoTemplate', TextareaType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.featureinfo_template.heading',
                'attr' => [
                    'class' => 'form-control featureinfo-template-textarea',
                    'rows' => 6,
                    'placeholder' => '<b>${name}</b><br>Year: ${year}',
                ],
            ])
            ->add('featureInfoMode', HiddenType::class, [
                'required' => false,
            ])
            ->add('layers', CollectionType::class, [
                'entry_type' => OgcApiFeaturesInstanceLayerType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'label' => 'mb.ogcapifeatures.admin.layers',
            ])
        ;
    }

}
