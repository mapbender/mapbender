<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\CoreBundle\Element\ViewManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ViewManagerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $accessChoices = [
            'mb.core.viewManager.admin.access.none' => '',
            'mb.core.viewManager.admin.access.ro' => ViewManager::ACCESS_READONLY,
            'mb.core.viewManager.admin.access.rw' => ViewManager::ACCESS_READWRITE,
            'mb.core.viewManager.admin.access.rwd' => ViewManager::ACCESS_READWRITEDELETE,
        ];
        $builder
            ->add('privateEntries', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.viewManager.admin.privateEntries',
            ])
            ->add('showDate', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.viewManager.admin.showDate',
            ])
            ->add('allowAnonymousSave', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.viewManager.admin.allowAnonymousSave',
            ])
           ->add('publicEntries', ChoiceType::class, [
               'choices' => $accessChoices,
               'required' => false,
               'label' => 'mb.core.viewManager.admin.publicEntries',
           ])
        ;
    }
}
