<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\WmsBundle\Element\Type\Subscriber\DimensionsHandlerMapTargetSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Paul Schmidt
 */
class DimensionsHandlerAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
        ;
        $builder->get('target')->addEventSubscriber(new DimensionsHandlerMapTargetSubscriber());
    }
}
