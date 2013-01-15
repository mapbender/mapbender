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
        $builder->add('title', 'text', array(
                'required' => false))
//            ->add('name', 'text', array(
//                'required' => false, "read_only" => true))
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
            ->add('allowreorder', 'checkbox', array(
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
                'choices' => range(0, $options["num_layers"] - 1, 1),
                'required'  => true));      
    }
}
