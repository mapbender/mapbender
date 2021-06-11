<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\Subscriber\BaseSourceSwitcherMapTargetSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BaseSourceSwitcherAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => false))
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
        ;
        $builder->get('target')->addEventSubscriber(new BaseSourceSwitcherMapTargetSubscriber());
    }
}
