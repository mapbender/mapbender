<?php


namespace Mapbender\CoreBundle\Element\Type;


use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\ManagerBundle\Form\Type\ApplicationChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class ApplicationSwitcherAdminType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applications', ApplicationChoiceType::class, array(
                'multiple' => true,
                'label' => 'mb.terms.application.plural',
                'attr' => array(
                    'size' => 20,
                ),
                'required_grant' => ResourceDomainApplication::ACTION_VIEW,
            ))
            ->add('open_in_new_tab', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.applicationSwitcher.admin.open_in_new_tab',
            ))
        ;
    }
}
