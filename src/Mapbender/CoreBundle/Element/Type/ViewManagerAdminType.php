<?php


namespace Mapbender\CoreBundle\Element\Type;


use Mapbender\CoreBundle\Element\ViewManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ViewManagerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accessChoices = array(
            'mb.core.viewManager.admin.access.none' => '',
            'mb.core.viewManager.admin.access.ro' => ViewManager::ACCESS_READONLY,
            'mb.core.viewManager.admin.access.rw' => ViewManager::ACCESS_READWRITE,
            'mb.core.viewManager.admin.access.rwd' => ViewManager::ACCESS_READWRITEDELETE,
        );
        $builder
            ->add('privateEntries', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.viewManager.admin.privateEntries',
            ))
            ->add('showDate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.viewManager.admin.showDate',
            ))
            ->add('allowAnonymousSave', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.viewManager.admin.allowAnonymousSave',
            ))
           ->add('publicEntries', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
               'choices' => $accessChoices,
               'required' => false,
               'label' => 'mb.core.viewManager.admin.publicEntries',
           ))
        ;
    }
}
