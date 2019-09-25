<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\WmsBundle\Element\Type\Subscriber\DimensionsHandlerMapTargetSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Paul Schmidt
 */
class DimensionsHandlerAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'dimensionshandler';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
        ;
        $builder->addEventSubscriber(new DimensionsHandlerMapTargetSubscriber($options['application']));
    }
}
