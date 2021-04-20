<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ApplicationSwitcherAdminType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('applications', 'Mapbender\ManagerBundle\Form\Type\ApplicationChoiceType', array(
                'multiple' => true,
                'label' => 'mb.terms.application.plural',
                'attr' => array(
                    'size' => 20,
                ),
                'required_grant' => 'VIEW',
            ))
            ->add('open_in_new_tab', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.applicationSwitcher.admin.open_in_new_tab',
            ))
        ;
    }
}
