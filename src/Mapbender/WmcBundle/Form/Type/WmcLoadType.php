<?php

namespace Mapbender\WmcBundle\Form\Type;

use Mapbender\WmcBundle\Form\EventListener\WmcFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

//use Symfony\Component\Form\FormBuilder;

class WmcLoadType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmcload';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new WmcFieldSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $builder->add('xml', 'file',
                      array('required' => true))
                ->add('state', 'hidden',
                      array(
                          'required' => false,
                          'data_class' => 'Mapbender\CoreBundle\Entity\State'));
    }

}
