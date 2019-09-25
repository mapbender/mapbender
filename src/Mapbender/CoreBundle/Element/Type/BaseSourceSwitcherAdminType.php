<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\Subscriber\BaseSourceSwitcherMapTargetSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseSourceSwitcherAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'element' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => false))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
        ;
        $builder->addEventSubscriber(new BaseSourceSwitcherMapTargetSubscriber($options['application']));
    }
}
