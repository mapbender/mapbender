<?php

namespace FOM\UserBundle\Form\Type;

use FOM\UserBundle\Security\Permission\AbstractAttributeDomain;
use FOM\UserBundle\Security\Permission\PermissionManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Form type for editing / assigning permissions.
 *
 * If available permission are not set, all permissions from AbstractAttributeDomain::getPermissions are selectable
 */
class PermissionListType extends AbstractType

{
    public function __construct(private PermissionManager $permissionManager)
    {

    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function getBlockPrefix()
    {
        return 'permission_list';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'attribute_domain' => null,
            'attribute' => null,
            'permission_list' => null,
            // Can never be mapped. Retrieval and storage goes through PermissionManager.
            'mapped' => false,
            'entry_options' => ['attribute_domain' => null],
            'entry_type' => PermissionType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'data' => fn (Options $options) => array_values($this->loadPermissions($options)),
        ]);
        $resolver->setAllowedValues('mapped', [false]);
        $resolver->setAllowedTypes('attribute_domain', [AbstractAttributeDomain::class]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['attribute_domain'] = $options['attribute_domain'];
    }

    protected function loadPermissions(Options $options): array
    {
        return $this->permissionManager->findPermissions($options['attribute_domain'], $options['attribute']);
    }

}
