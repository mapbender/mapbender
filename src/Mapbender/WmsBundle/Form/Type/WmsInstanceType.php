<?php
namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Mapbender\WmsBundle\Entity\WmsInstance;

//use Mapbender\ManagerBundle\Form\Type\BaseElementType;

class WmsInstanceType extends AbstractType {
    
    protected $wmsinstance;
    
    public function __construct(WmsInstance $wmsinstance) {
        $this->wmsinstance = $wmsinstance;
    }
    
    public function getName() {
        return 'wmsinstance';
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
            $builder->add('title', 'text', array(
                'label' => 'title',
                'required'  => false));
            $arr = $this->wmsinstance->getWmssource()->getGetMap()->getFormats()!== null?
                    $this->wmsinstance->getWmssource()->getGetMap()->getFormats(): array();
            $formats = array();
            foreach ($arr as $value) {
                $formats[$value] = $value;
            }
            $builder->add('format', 'choice', array(
                'label' => 'format',
                'choices' => $formats,
                'required'  => true));
            $arr = $this->wmsinstance->getWmssource()->getGetFeatureInfo()->getFormats()!== null?
                    $this->wmsinstance->getWmssource()->getGetFeatureInfo()->getFormats(): array();
            $formats = array();
            foreach ($arr as $value) {
                $formats[$value] = $value;
            }
            $builder->add('infoformat', 'choice', array(
                'label' => 'infoformat',
                'choices' => $formats,
                'required'  => true));
            $arr = $this->wmsinstance->getWmssource()->getExceptionFormats()!== null?
                    $this->wmsinstance->getWmssource()->getExceptionFormats(): array();
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
    }
}
