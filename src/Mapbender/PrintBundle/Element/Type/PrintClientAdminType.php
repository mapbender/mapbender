<?php
namespace Mapbender\PrintBundle\Element\Type;

use Mapbender\ManagerBundle\Form\DataTransformer\ArrayToCsvScalarTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
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
            ->add('scales', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('file_prefix', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
        ;
        $builder->get('scales')->addViewTransformer(new ArrayToCsvScalarTransformer());
        if ($this->queueable) {
            $builder->add('renderMode', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => false,            // FOM form theme fails to translate preselected label with required = true
                'choices' => array(
                    'mb.print.admin.printclient.renderMode.choice.direct' => 'direct',
                    'mb.print.admin.printclient.renderMode.choice.queued' => 'queued',
                ),
                'choices_as_values' => true,
                'label' => 'mb.print.admin.printclient.renderMode.label',
            ));
            $builder->add('queueAccess', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => false,            // FOM form theme fails to translate preselected label with required = true
                'choices' => array(
                    'mb.print.admin.printclient.queueAccess.choice.private' => 'private',
                    'mb.print.admin.printclient.queueAccess.choice.global' => 'global',
                ),
                'choices_as_values' => true,
                'label' => 'mb.print.admin.printclient.queueAccess.label',
            ));
        }
        $builder
            ->add('quality_levels', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'auto_initialize' => false,
                'required' => false,
                'entry_type' => 'Mapbender\PrintBundle\Element\Type\PrintClientQualityAdminType',
            ))
            ->add('rotatable', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.rotatable',
            ))
            ->add('legend', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.legend',
            ))
            ->add('legend_default_behaviour', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.legend_default_behaviour',
            ))
            ->add('optional_fields', 'Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType', array(
                'required' => false,
            ))
            ->add('required_fields_first', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.required_fields_first',
            ))
            ->add('replace_pattern', 'Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType', array(
                'required' => false,
            ))
            ->add('templates', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'entry_type' => 'Mapbender\PrintBundle\Element\Type\PrintClientTemplateAdminType',
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }
}
