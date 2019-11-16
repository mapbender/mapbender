<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\EventListener\LayertreeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LayertreeAdminType
 */
class LayertreeAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'layertree';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new LayertreeSubscriber($options['application']);
        $builder->addEventSubscriber($subscriber);
        $builder->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    'Element' => 'element',
                    'Dialog' => 'dialog',
                ),
                'choices_as_values' => true,
            ))
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.autoopen',
            ))
            ->add('useTheme', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.usetheme',
            ))
            ->add('showBaseSource', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.showbasesources',
            ))
            ->add('showHeader', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.showheader',
            ))
            ->add('hideInfo', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.hideinfo',
            ))
            ->add('hideNotToggleable', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.hidenottoggleable',
            ))
            ->add('hideSelect', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.hideselect',
            ))
            ->add('menu', 'Mapbender\CoreBundle\Element\Type\LayerTreeMenuType', array(
                'required' => false,
            ))
        ;
    }
}
