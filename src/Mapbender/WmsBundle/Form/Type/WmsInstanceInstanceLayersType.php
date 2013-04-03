<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * WmsInstanceInstanceLayersType class
 */
class WmsInstanceInstanceLayersType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmsinstanceinstancelayers';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_templates' => array(),
            'gfg' => function(FormInterface $form)
            {
                $data = $form->getData()->getWmssourcelayer();
                return true;
            }));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $wmsinstance = $options["data"];
        $arr = $wmsinstance->getSource()->getGetMap()->getFormats() !== null ?
                $wmsinstance->getSource()->getGetMap()->getFormats() : array();
        $formats = array();
        foreach($arr as $value)
        {
            $formats[$value] = $value;
        }
        $builder->add('format', 'choice',
                      array(
            'choices' => $formats,
            'required' => true));
        $arr = $wmsinstance->getSource()->getGetFeatureInfo()->getFormats() !== null
                    ?
                $wmsinstance->getSource()->getGetFeatureInfo()->getFormats() : array();
        $formats = array();
        foreach($arr as $value)
        {
            $formats[$value] = $value;
        }
        $builder->add('infoformat', 'choice',
                      array(
            'choices' => $formats,
            'required' => true));
        $arr = $wmsinstance->getSource()->getExceptionFormats() !== null ?
                $wmsinstance->getSource()->getExceptionFormats() : array();
        $formats = array();
        foreach($arr as $value)
        {
            $formats[$value] = $value;
        }
        $opacity = array();
        foreach(range(0, 100, 10) as $value)
        {
            $opacity[$value] = $value;
        }
        $builder->add('exceptionformat', 'choice',
                      array(
                    'choices' => $formats,
                    'required' => true))
                ->add('visible', 'checkbox',
                      array(
                    'required' => false))
                ->add('proxy', 'checkbox',
                      array(
                    'required' => false))
                ->add('opacity', 'choice',
                      array(
                    'choices' => $opacity, //range(0, 100),
                    'required' => true))
                ->add('transparency', 'checkbox',
                      array(
                    'required' => false))
                ->add('tiled', 'checkbox',
                      array(
                    'required' => false))
                ->add('baselayer', 'checkbox',
                      array(
                    'required' => false))
                ->add('layers', 'collection',
                      array(
                    'type' => new WmsInstanceLayerType(),
                    'options' => array(
                        'data_class' => 'Mapbender\WmsBundle\Entity\WmsInstanceLayer',
                        'num_layers' => count($wmsinstance->getLayers()))
                ));
    }

}
