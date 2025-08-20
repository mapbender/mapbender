<?php

namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class LayertreeAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ))
            ->add('useTheme', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.usetheme',
            ))
            ->add('allowReorder', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowreordertoc',
            ))
            ->add('showBaseSource', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.showbasesources',
            ))
            ->add('hideInfo', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.hideinfo',
            ))
            ->add('menu', $this->getMenuCollectionType(), array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.menu',
            ))
            ->add('themes', LayertreeThemeCollectionType::class, array(
                'label' => 'mb.core.admin.layertree.label.themes',
                'required' => false,
            ))
            ->add('showFilter', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.layertree.label.filter',
            ))
        ;
    }

    public function getMenuCollectionType(): string
    {
        return LayerTreeMenuType::class;
    }
}
