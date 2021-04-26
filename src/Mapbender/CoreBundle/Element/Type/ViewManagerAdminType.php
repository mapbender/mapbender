<?php


namespace Mapbender\CoreBundle\Element\Type;


use Mapbender\CoreBundle\Element\ViewManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;

class ViewManagerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = array();
        if (Kernel::MAJOR_VERSION < 3) {
            $choiceOptions['choices_as_values'] = true;
        }
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
           ->add('publicEntries', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $choiceOptions + array(
               'choices' => $accessChoices,
               'required' => false,
               'label' => 'mb.core.viewManager.admin.publicEntries',
           ))
        ;
    }
}
