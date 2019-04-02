<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 */
class FeatureInfoAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'featureinfo';
    }

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
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
            ->add('displayType', 'choice', array(
                'required' => true,
                'choices' => array('tabs' => 'Tabs', 'accordion' => 'Accordion')))
            ->add('autoActivate', 'checkbox', array('required' => false))
            ->add('printResult', 'checkbox', array('required' => false))
            ->add('deactivateOnClose', 'checkbox', array('required' => false))
            ->add('showOriginal', 'checkbox', array('required' => false))
            ->add('onlyValid', 'checkbox', array('required' => false))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('width', 'integer', array('required' => true))
            ->add('height', 'integer', array('required' => true))
            ->add('maxCount', 'integer', array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 100,
                ),
            ))
        ;
    }
}
