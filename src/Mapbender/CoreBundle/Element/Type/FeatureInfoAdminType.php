<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeatureInfoAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'maxCount' => 100,
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
            ->add('type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    'Dialog' => 'dialog',
                    'Element' => 'element',
                ),
                'choices_as_values' => true,
            ))
            ->add('displayType', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    'Tabs' => 'tabs',
                    'Accordion' => 'accordion',
                ),
                'choices_as_values' => true,
            ))
            ->add('autoActivate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.autoopen',
            ))
            ->add('printResult', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('deactivateOnClose', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.deactivateonclose',
            ))
            ->add('showOriginal', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.showoriginal',
            ))
            ->add('onlyValid', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.onlyvalid',
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => true,
            ))
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => true,
            ))
            ->add('maxCount', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 100,
                ),
            ))
        ;
    }
}
