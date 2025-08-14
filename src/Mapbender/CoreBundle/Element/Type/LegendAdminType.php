<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LegendAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(
        protected TranslatorInterface $translator,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ))
            ->add('showSourceTitle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showsourcetitle',
            ))
            ->add('showLayerTitle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showlayertitle',
            ))
            ->add('showGroupedLayerTitle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showgroupedlayertitle',
            ))
            ->add('dynamicLegend', CheckboxType::class, $this->createInlineHelpText(array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.dynamiclegend',
                'help' => 'mb.core.admin.legend.label.dynamiclegend_help',
            ), $this->translator))
        ;
    }

}
