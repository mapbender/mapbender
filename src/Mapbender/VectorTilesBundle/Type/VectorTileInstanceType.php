<?php

namespace Mapbender\VectorTilesBundle\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VectorTileInstanceType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $translator) {}

    public function getParent()
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minScale', IntegerType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.min_scale',
            ])
            ->add('maxScale', IntegerType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.max_scale',
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.selected',
            ])
            ->add('allowSelected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowselecttoc',
            ])
            ->add('featureInfo', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.active',
            ])
            ->add('featureInfoAllowToggle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.allowtoggle',
            ])
            ->add('featureInfoTitle', TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.title',
                'help' => 'mb.vectortiles.admin.featureinfo.title_help',
            ], $this->translator))
            ->add('hideIfNoTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.hide_if_no_title',
            ])
            ->add('propertyMap', YAMLConfigurationType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.vectortiles.admin.featureinfo.property_map',
                'help' => 'mb.vectortiles.admin.featureinfo.property_map_help',
            ], $this->translator))
        ;
    }
}
