<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\PrintBundle\Form\EventListener\PrintClientSubscriber;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;

/**
 * 
 */
class PrintClientAdminType extends AbstractType
{
    /** @var bool */
    protected $queueable;

    /**
     * @param bool $queuable
     */
    public function __construct($queuable)
    {
        $this->queueable = $queuable;
    }

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
        $subscriber = new PrintClientSubscriber($builder->getFormFactory(), $options["application"]);
        $builder->addEventSubscriber($subscriber);
        $builder
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
            ->add('type', 'choice', array(
                    'required' => true,
                    'choices' => array(
                        'dialog' => 'Dialog',
                        'element' => 'Element',
                    ),
            ))
            ->add('scales', 'text', array(
                'required' => false,
            ))
            ->add('file_prefix', 'text', array(
                'required' => false,
            ))
        ;
        if ($this->queueable) {
            $builder->add('renderMode', 'choice', array(
                'required' => false,            // FOM form theme fails to translate preselected label with required = true
                'choices' => array(
                    'direct' => 'mb.print.admin.printclient.renderMode.choice.direct',
                    'queued' => 'mb.print.admin.printclient.renderMode.choice.queued',
                ),
                'choices_as_values' => false,   // FOM form theme fails to translate labels with choice_as_values = true
                'label' => 'mb.print.admin.printclient.renderMode.label',
            ));
            $builder->add('queueAccess', 'choice', array(
                'required' => false,            // FOM form theme fails to translate preselected label with required = true
                'choices' => array(
                    'private' => 'mb.print.admin.printclient.queueAccess.choice.private',
                    'global' => 'mb.print.admin.printclient.queueAccess.choice.global',
                ),
                'choices_as_values' => false,   // FOM form theme fails to translate labels with choice_as_values = true
                'label' => 'mb.print.admin.printclient.queueAccess.label',
            ));
        }
        $builder
            ->add('rotatable', 'checkbox', array(
                'required' => false,
            ))
            ->add('legend', 'checkbox', array(
                'required' => false,
            ))
            ->add('legend_default_behaviour', 'checkbox', array(
                'required' => false,
            ))
            ->add('optional_fields', new YAMLConfigurationType(), array(
                'required' => false,
            ))
            ->add('required_fields_first', 'checkbox', array(
                'required' => false,
            ))
            ->add('replace_pattern', new YAMLConfigurationType(), array(
                'required' => false,
            ))
            ->add('templates', 'collection', array(
                'type' => new PrintClientTemplateAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }
}
