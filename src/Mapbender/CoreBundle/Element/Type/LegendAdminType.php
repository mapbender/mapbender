<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LegendAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ])
            ->add('showSourceTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showsourcetitle',
            ])
            ->add('showLayerTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showlayertitle',
            ])
            ->add('showGroupedLayerTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showgroupedlayertitle',
            ])
        ;
    }

}
