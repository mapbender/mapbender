<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * WmisInstanceInstanceLayersType class
 */
class WmtsInstanceInstanceLayersType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmtsinstanceinstancelayers';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'available_templates' => array(),
                'gfg' => function (FormInterface $form) {
                    $data = $form->getData()->getWmtssourcelayer();
                    return true;
                }
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $wmtsinstance = $options["data"];
//        $arr = $wmtsinstance->getSource()->getGetMap()->getFormats() !== null ?
//            $wmtsinstance->getSource()->getGetMap()->getFormats() : array();
//        $formats = array();
//        foreach ($arr as $value) {
//            $formats[$value] = $value;
//        }
//        $builder;
//            ->add('format', 'choice', array(
//                'choices' => $formats,
//                'required' => true));
//        $gfi = $wmtsinstance->getSource()->getGetFeatureInfo();
//        $arr = $gfi && $gfi->getFormats() !== null ? $gfi->getFormats() : array();
//        $formats_gfi = array();
//        foreach ($arr as $value) {
//            $formats_gfi[$value] = $value;
//        }
//        $builder->add('infoformat', 'choice',
//                      array(
//            'choices' => $formats_gfi,
//            'required' => false));
//        $arr = $wmtsinstance->getSource()->getExceptionFormats() !== null ?
//            $wmtsinstance->getSource()->getExceptionFormats() : array();
//        $formats_exc = array();
//        foreach ($arr as $value) {
//            $formats_exc[$value] = $value;
//        }
        $opacity = array();
        foreach (range(0, 100, 10) as $value) {
            $opacity[$value] = $value;
        }
        $builder
            ->add('title', 'text', array(
                'required' => true))
            ->add('basesource', 'checkbox', array(
                'required' => false))
            ->add('visible', 'checkbox', array(
                'required' => false))
            ->add('proxy', 'checkbox', array(
                'required' => false))
            ->add('opacity', 'choice', array(
                'choices' => $opacity,
                'required' => true))
            ->add('layers', 'collection', array(
                'type' => new WmtsInstanceLayerType(),
                'options' => array(
                    'data_class' => 'Mapbender\WmtsBundle\Entity\WmtsInstanceLayer',
                    'num_layers' => count($wmtsinstance->getLayers()))))

            ->add('roottitle', 'text', array(
                'required' => true))
            ->add('active', 'checkbox', array(
                'required' => false))
            ->add('selected', 'checkbox', array(
                'required' => false))
            ->add('info', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('toggle', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('allowselected', 'checkbox', array(
                'required' => false))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('allowtoggle', 'checkbox', array(
                'required' => false,
                'disabled' => true))
        ;
    }
}
