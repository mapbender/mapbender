<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WmsInstanceInstanceLayersType extends AbstractType
{
    use MapbenderTypeTrait;

    /**
     * @param bool $exposeLayerOrder to expose layer order controls; from parameter mapbender.preview.layer_order.wms
     */
    public function __construct(
        protected bool $exposeLayerOrder = false,
        protected ?TranslatorInterface $translator = null)
    {
    }

    public function getParent(): string
    {
        return SourceInstanceType::class;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WmsInstance $instance */
        $instance = $options["data"];
        $source = $instance->getSource();

        $getMapFormatChoices = [];
        foreach ($source->getGetMap()->getFormats() ?: [] as $value) {
            $getMapFormatChoices[$value] = $value;
        }
        $featureInfoFormatChoices = [];
        if ($gfi = $source->getGetFeatureInfo()) {
            foreach ($gfi->getFormats() ?: [] as $value) {
                $featureInfoFormatChoices[$value] = $value;
            }
        }
        $exceptionFormatChoices = [];
        foreach ($source->getExceptionFormats() ?: [] as $value) {
            $exceptionFormatChoices[$value] = $value;
        }

        $builder
            ->add('format', ChoiceType::class, [
                'choices' => $getMapFormatChoices,
                'required' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.format',
            ])
            ->add('infoformat', ChoiceType::class, [
                'choices' => $featureInfoFormatChoices,
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.infoformat',
            ])
            ->add('exceptionformat', ChoiceType::class, [
                'choices' => $exceptionFormatChoices,
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.exceptionformat',
            ])
            ->add('transparency', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.transparency',
            ])
            ->add('tiled', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.tiled',
            ])
            ->add('ratio', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'label' => 'mb.wms.wmsloader.repo.instance.label.ratio',
            ])
            ->add('buffer', IntegerType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.buffer',
            ])
            ->add('refreshInterval', IntegerType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.refresh_interval',
                'help' => 'mb.wms.wmsloader.repo.instance.label.refresh_interval_help',
            ], $this->translator))
        ;
        if ($source->getDimensions()) {
            $builder->add('dimensions', CollectionType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.dimensions',
                'entry_type' => DimensionInstType::class,
                'allow_add' => false,
                'allow_delete' => false,
                'entry_options' => [
                    'instance' => $instance,
                    'by_reference' => false,
                ],
                'label' => false,
            ]);
        }
        $builder
            ->add('vendorspecifics', CollectionType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.vendorspecifics',
                'entry_type' => VendorSpecificType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'by_reference' => false,
                ],
            ])
            ->add('layers', SourceInstanceLayerCollectionType::class, [
                'entry_type' => WmsInstanceLayerType::class,
                'label' => 'mb.wms.wmsloader.repo.instance.label.layers',
                'entry_options' => [
                    'data_class' => WmsInstanceLayer::class,
                ],
            ])
        ;

        if ($this->exposeLayerOrder) {
            $layerOrderChoices = [];
            foreach (WmsInstance::validLayerOrderChoices() as $validChoice) {
                $translationKey = "mb.wms.wmsloader.repo.instance.label.layerorder.$validChoice";
                $layerOrderChoices[$translationKey] = $validChoice;
            }
            $builder->add('layerOrder', ChoiceType::class, [
                'choices' => $layerOrderChoices,
                'required' => true,
                'auto_initialize' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.layerorder',
            ]);
        }
    }
}
