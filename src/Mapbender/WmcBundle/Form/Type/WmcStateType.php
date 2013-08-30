<?php

namespace Mapbender\WmcBundle\Form\Type;

use Mapbender\WmcBundle\Form\EventListener\WmcFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

//use Symfony\Component\Form\FormBuilder;

class WmcStateType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmcstate';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $subscriber = new WmcFieldSubscriber($builder->getFormFactory());
//        $builder->addEventSubscriber($subscriber);
        $builder->add('state', 'hidden',
                      array(
                          'required' => false,
                          'data_class' => 'Mapbender\CoreBundle\Entity\State'));
    }

}
