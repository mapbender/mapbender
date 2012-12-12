<?php
namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\WmsBundle\Form\EventListener\FieldSubscriber;

//use Mapbender\WmsBundle\Entity\WmsInstance;

//use Mapbender\ManagerBundle\Form\Type\BaseElementType;

class WmsInstanceLayerType extends AbstractType {
    /*
    protected $wmsinstance;
    
    public function __construct(WmsInstance $wmsinstance) {
        $this->wmsinstance = $wmsinstance;
    }
     */
    
    public function getName() {
        return 'wmsinstancelayer';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'num_layers' => 0));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $subscriber = new FieldSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $choices = range(1, $options["num_layers"]);
        $builder->add('title', 'text', array(
                    'required' => false))
            ->add('active', 'checkbox', array(
                        'required' => false))
            ->add('selected', 'checkbox', array(
                        'required' => false))
            ->add('selected_default', 'checkbox', array(
                        'required' => false))
            ->add('gfinfo', 'checkbox', array(
                        'required' => false,
                        'disabled' => true))
            ->add('gfinfo_default', 'checkbox', array(
                        'required' => false,
                        'disabled' => true))
            ->add('minScale', 'text', array(
                    'required' => false))
            ->add('maxScale', 'text', array(
                    'required' => false))
            ->add('style', 'choice', array(
                    'label' => 'style',
                    'choices' => array(),
                    'required'  => false))
            ->add('priority', 'choice', array(
                    'label' => 'priority',
                    'choices' => $choices,
                    'required'  => true));      
    }
}
