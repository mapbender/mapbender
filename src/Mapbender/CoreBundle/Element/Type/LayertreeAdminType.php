<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\EventListener\LayertreeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        $subscriber = new LayertreeSubscriber($builder->getFormFactory(), $options['application']);
        $builder->addEventSubscriber($subscriber);
        $builder->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => array(
                    'element' => 'Element',
                    'dialog' => 'Dialog')))
            ->add('autoOpen', 'checkbox', array(
                'required' => false))
            ->add('useTheme', 'checkbox', array(
                'required' => false))
            ->add('displaytype', 'choice', array(
                'required' => true,
                'choices' => array('tree' => 'Tree')))
            ->add('titlemaxlength', 'text', array(
                'required' => true))
            ->add('showBaseSource', 'checkbox', array(
                'required' => false))
            ->add('showHeader', 'checkbox', array(
                'required' => false))
            ->add('hideInfo', 'checkbox', array(
                'required' => false))
            ->add('hideNotToggleable', 'checkbox', array(
                'required' => false))
            ->add('hideSelect', 'checkbox', array(
                'required' => false))
            // see LayerTreeMenuType.php
            ->add('menu', 'layertree_menu', array(
                'required' => false,
            ))
        ;
    }
}
