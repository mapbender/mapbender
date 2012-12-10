<?php
namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WmsInstanceInstanceLayersType extends AbstractType
{
    public function getName()
    {
        return 'wmsinstanceinstancelayers';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_templates' => array(),
            'gfg' => function(FormInterface $form) {
                $data = $form->getData()->getWmssourcelayer();
                return true;
            }));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $wmsinstance = $options["data"];
        $arr = $wmsinstance->getWmssource()->getGetMap()->getFormats()!== null?
                    $wmsinstance->getWmssource()->getGetMap()->getFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $builder->add('format', 'choice', array(
            'label' => 'format',
            'choices' => $formats,
            'required'  => true));
        $arr = $wmsinstance->getWmssource()->getGetFeatureInfo()->getFormats()!== null?
                $wmsinstance->getWmssource()->getGetFeatureInfo()->getFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $builder->add('infoformat', 'choice', array(
            'label' => 'infoformat',
            'choices' => $formats,
            'required'  => true));
        $arr = $wmsinstance->getWmssource()->getExceptionFormats()!== null?
                $wmsinstance->getWmssource()->getExceptionFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $builder->add('exceptionformat', 'choice', array(
            'label' => 'exceptionformat',
            'choices' => $formats,
            'required'  => true));            
        $builder->add('visible', 'checkbox', array(
            'label' => 'visible',
            'required'  => false));
        $builder->add('proxy', 'checkbox', array(
            'label' => 'proxy',
            'required'  => false));
        $builder->add('opacity', 'checkbox', array(
            'label' => 'opacity',
            'required'  => false));
        $builder->add('transparency', 'checkbox', array(
            'label' => 'transparency',
            'required'  => false));
        $builder->add('tiled', 'checkbox', array(
            'label' => 'tiled',
            'required'  => false));
        $builder->add('layers', 'collection', array(
           'type' => new WmsInstanceLayerType(),
            'options' => array(
                'data_class' => 'Mapbender\WmsBundle\Entity\WmsInstanceLayer',
//                'data' => $wmsinstance
                'num_layers' => count($wmsinstance->getLayers())
            )
        ));
    }
}
