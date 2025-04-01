<?php
namespace Mapbender\PrintBundle\Element\Type;

use Mapbender\ManagerBundle\Form\DataTransformer\ArrayToCsvScalarTransformer;
use Mapbender\ManagerBundle\Form\DataTransformer\IntArrayToCsvScalarTransformer;
use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PrintClientAdminType extends AbstractType
{
    public function __construct(protected bool $queueable)
    {
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scales', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.printclient.admin.scales',
            ))
            ->add('file_prefix', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.printclient.admin.fileprefix',
            ))
        ;
        $builder->get('scales')->addViewTransformer(new IntArrayToCsvScalarTransformer());
        if ($this->queueable) {
            $builder->add('renderMode', ChoiceType::class, array(
                'choices' => array(
                    'mb.print.admin.printclient.renderMode.choice.direct' => 'direct',
                    'mb.print.admin.printclient.renderMode.choice.queued' => 'queued',
                ),
                'label' => 'mb.print.admin.printclient.renderMode.label',
            ));
            $builder->add('queueAccess', ChoiceType::class, array(
                'choices' => array(
                    'mb.print.admin.printclient.queueAccess.choice.private' => 'private',
                    'mb.print.admin.printclient.queueAccess.choice.global' => 'global',
                ),
                'label' => 'mb.print.admin.printclient.queueAccess.label',
            ));
        }
        $builder
            ->add('quality_levels', SortableCollectionType::class, array(
                'label' => 'mb.core.admin.printclient.label.qualitylevels',
                'auto_initialize' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => PrintClientQualityAdminType::class,
            ))
            ->add('rotatable', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.rotatable',
            ))
            ->add('legend', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.legend',
            ))
            ->add('legend_default_behaviour', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.legend_default_behaviour',
            ))
            ->add('optional_fields', YAMLConfigurationType::class, array(
                'required' => false,
                'label' => 'mb.core.printclient.admin.optionalfields',
            ))
            ->add('required_fields_first', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.printclient.label.required_fields_first',
            ))
            ->add('replace_pattern', YAMLConfigurationType::class, array(
                'required' => false,
                'label' => 'mb.core.printclient.admin.replacepattern',
            ))
            ->add('templates', SortableCollectionType::class, array(
                'label' => 'mb.core.admin.printclient.label.templates',
                'entry_type' => PrintClientTemplateAdminType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }
}
