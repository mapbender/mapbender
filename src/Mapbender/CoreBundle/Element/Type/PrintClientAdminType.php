<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Form\EventListener\PrintClientSubscriber;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Mapbender\CoreBundle\Element\Type\PrintClientTemplateAdminType;

/**
 * 
 */
class PrintClientAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'printclient';
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
        $subscriber = new PrintClientSubscriber($builder->getFormFactory(),
            $options["application"]);
        $builder->addEventSubscriber($subscriber);
        $builder->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('type', 'choice', array(
                    'required' => true,
                    'choices' => array(
                        'dialog' => 'Dialog',
                        'element' => 'Element')))
            ->add('scales', 'text', array('required' => false))
            ->add('file_prefix', 'text', array('required' => false))
            ->add('rotatable', 'checkbox',array('required' => false))
            ->add('legend', 'checkbox',array('required' => false))
            ->add('legend_default_behaviour', 'checkbox',array('required' => false))
            ->add('optional_fields', new YAMLConfigurationType(), array('required' => false,'attr' => array('class' => 'code-yaml')))
            ->add('required_fields_first', 'checkbox', array(
                'required' => false,
            ))
            ->add('replace_pattern', new YAMLConfigurationType(),array('required' => false,'attr' => array('class' => 'code-yaml')))
            ->add('templates', 'collection', array(
                'type' => new PrintClientTemplateAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ));
    }

}