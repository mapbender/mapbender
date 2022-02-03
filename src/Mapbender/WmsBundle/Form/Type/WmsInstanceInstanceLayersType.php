<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmsInstanceInstanceLayersType extends AbstractType
{
    /** @var bool */
    protected $exposeLayerOrder;

    /**
     * @param bool $exposeLayerOrder to expose layer order controls; from parameter mapbender.preview.layer_order.wms
     */
    public function __construct($exposeLayerOrder = false)
    {
        $this->exposeLayerOrder = $exposeLayerOrder;
    }

    public function getParent()
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ->add('format', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $getMapFormatChoices,
                'required' => true,
            ))
            ->add('infoformat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $featureInfoFormatChoices,
                'required' => false,
            ))
            ->add('exceptionformat', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $exceptionFormatChoices,
                'required' => false,
            ))
            ->add('transparency', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.transparency',
            ))
            ->add('tiled', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.tiled',
            ))
            ->add('ratio', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'required' => false,
                'scale' => 2,
                'label' => 'mb.wms.wmsloader.repo.instance.label.ratio',
            ))
            ->add('buffer', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.buffer',
            ))
        ;
        if ($source->getDimensions()) {
            $builder->add('dimensions', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'required' => false,
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
            ->add('vendorspecifics', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'required' => false,
                'entry_type' => 'Mapbender\WmsBundle\Form\Type\VendorSpecificType',
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => array(
                    'by_reference' => false,
                ),
            ))
            ->add('layers', 'Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType', array(
                'entry_type' => 'Mapbender\WmsBundle\Form\Type\WmsInstanceLayerType',
                'entry_options' => array(
                    'data_class' => 'Mapbender\WmsBundle\Entity\WmsInstanceLayer',
                ),
            ))
        ;

        if ($this->exposeLayerOrder) {
            $layerOrderChoices = array();
            foreach (WmsInstance::validLayerOrderChoices() as $validChoice) {
                $translationKey = "mb.wms.wmsloader.repo.instance.label.layerOrder.$validChoice";
                $layerOrderChoices[$translationKey] = $validChoice;
            }
            $builder->add('layerOrder', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $layerOrderChoices,
                'required' => true,
                'auto_initialize' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.layerOrder',
            ));
        }
    }
}
