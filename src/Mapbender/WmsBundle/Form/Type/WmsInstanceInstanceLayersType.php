<?php

namespace Mapbender\WmsBundle\Form\Type;

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
        protected TranslatorInterface $translator)
    {
    }

    public function getParent(): string
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WmsInstance $instance */
        $instance = $options["data"];
        $source = $instance->getSource();

        $getMapFormatChoices = array();
        foreach ($source->getGetMap()->getFormats() ?: array() as $value) {
            $getMapFormatChoices[$value] = $value;
        }
        $featureInfoFormatChoices = array();
        if ($gfi = $source->getGetFeatureInfo()) {
            foreach ($gfi->getFormats() ?: array() as $value) {
                $featureInfoFormatChoices[$value] = $value;
            }
        }
        $exceptionFormatChoices = array();
        foreach ($source->getExceptionFormats() ?: array() as $value) {
            $exceptionFormatChoices[$value] = $value;
        }

        $builder
            ->add('format', ChoiceType::class, array(
                'choices' => $getMapFormatChoices,
                'required' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.format',
            ))
            ->add('infoformat', ChoiceType::class, array(
                'choices' => $featureInfoFormatChoices,
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.infoformat',
            ))
            ->add('exceptionformat', ChoiceType::class, array(
                'choices' => $exceptionFormatChoices,
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.exceptionformat',
            ))
            ->add('transparency', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.transparency',
            ))
            ->add('tiled', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.tiled',
            ))
            ->add('ratio', NumberType::class, array(
                'required' => false,
                'scale' => 2,
                'label' => 'mb.wms.wmsloader.repo.instance.label.ratio',
            ))
            ->add('buffer', IntegerType::class, array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.buffer',
            ))
            ->add('refreshInterval', IntegerType::class, $this->createInlineHelpText(array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.refresh_interval',
                'help' => 'mb.wms.wmsloader.repo.instance.label.refresh_interval_help',
            ), $this->translator))
        ;
        if ($source->getDimensions()) {
            $builder->add('dimensions', CollectionType::class, array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.dimensions',
                'entry_type' => 'Mapbender\WmsBundle\Form\Type\DimensionInstType',
                'allow_add' => false,
                'allow_delete' => false,
                'entry_options' => array(
                    'instance' => $instance,
                    'by_reference' => false,
                ),
                'label' => false,
            ));
        }
        $builder
            ->add('vendorspecifics', CollectionType::class, array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.vendorspecifics',
                'entry_type' => 'Mapbender\WmsBundle\Form\Type\VendorSpecificType',
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => array(
                    'by_reference' => false,
                ),
            ))
            ->add('layers', SourceInstanceLayerCollectionType::class, array(
                'entry_type' => 'Mapbender\WmsBundle\Form\Type\WmsInstanceLayerType',
                'label' => 'mb.wms.wmsloader.repo.instance.label.layers',
                'entry_options' => array(
                    'data_class' => 'Mapbender\WmsBundle\Entity\WmsInstanceLayer',
                ),
            ))
        ;

        if ($this->exposeLayerOrder) {
            $layerOrderChoices = array();
            foreach (WmsInstance::validLayerOrderChoices() as $validChoice) {
                $translationKey = "mb.wms.wmsloader.repo.instance.label.layerorder.$validChoice";
                $layerOrderChoices[$translationKey] = $validChoice;
            }
            $builder->add('layerOrder', ChoiceType::class, array(
                'choices' => $layerOrderChoices,
                'required' => true,
                'auto_initialize' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.layerorder',
            ));
        }
    }
}
