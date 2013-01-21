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
//        $builder->add('title', 'text');
        $wmsinstance = $options["data"];
        $arr = $wmsinstance->getSource()->getGetMap()->getFormats()!== null?
                    $wmsinstance->getSource()->getGetMap()->getFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $builder->add('format', 'choice', array(
//            'label' => 'format',
            'choices' => $formats,
            'required'  => true));
        $arr = $wmsinstance->getSource()->getGetFeatureInfo()->getFormats()!== null?
                $wmsinstance->getSource()->getGetFeatureInfo()->getFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $builder->add('infoformat', 'choice', array(
//            'label' => 'infoformat',
            'choices' => $formats,
            'required'  => true));
        $arr = $wmsinstance->getSource()->getExceptionFormats()!== null?
                $wmsinstance->getSource()->getExceptionFormats(): array();
        $formats = array();
        foreach ($arr as $value) {
            $formats[$value] = $value;
        }
        $opacity = array();
        foreach (range(0, 100, 10) as $value) {
            $opacity[$value] = $value;
        }
        $builder->add('exceptionformat', 'choice', array(
//                    'label' => 'exceptionformat',
                    'choices' => $formats,
                    'required'  => true))
                ->add('visible', 'checkbox', array(
//                    'label' => 'visible',
                    'required'  => false))
                ->add('proxy', 'checkbox', array(
//                    'label' => 'proxy',
                    'required'  => false))
                ->add('opacity', 'choice', array(
//                            'label' => 'opacity',
                    'choices' => $opacity,//range(0, 100),
                    'required'  => true))
                ->add('transparency', 'checkbox', array(
//                    'label' => 'transparency',
                    'required'  => false))
                ->add('tiled', 'checkbox', array(
//                    'label' => 'tiled',
                    'required'  => false))
                ->add('info', 'checkbox', array(
//                    'label' => 'info',
                    'required'  => false))
                ->add('selected', 'checkbox', array(
//                    'label' => 'selected',
                    'required'  => false))
                ->add('toggle', 'checkbox', array(
//                    'label' => 'toggle',
                    'required'  => false))
                ->add('allowinfo', 'checkbox', array(
//                    'label' => 'allowinfo',
                    'required'  => false))
                ->add('allowselected', 'checkbox', array(
//                    'label' => 'allowselected',
                    'required'  => false))
                ->add('allowtoggle', 'checkbox', array(
//                    'label' => 'allowtoggle',
                    'required'  => false))
                ->add('allowreorder', 'checkbox', array(
//                    'label' => 'allowreorder',
                    'required'  => false))
                ->add('layers', 'collection', array(
                    'type' => new WmsInstanceLayerType(),
                    'options' => array(
                        'data_class' => 'Mapbender\WmsBundle\Entity\WmsInstanceLayer',
                        'num_layers' => count($wmsinstance->getLayers()))
        ));
    }
}
