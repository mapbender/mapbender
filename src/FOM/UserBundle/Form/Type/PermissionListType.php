<?php

namespace FOM\UserBundle\Form\Type;

use FOM\UserBundle\Security\Permission\AbstractResourceDomain;
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
 * If available actions are not set, all actions from AbstractResourceDomain::getActions are selectable
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
            'resource_domain' => null,
            'resource' => null,
            'permission_list' => null,
            'show_public_access' => false,
            // Can never be mapped. Retrieval and storage goes through PermissionManager.
            'mapped' => false,
            'entry_options' => ['resource_domain' => null],
            'entry_type' => PermissionType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'data' => fn (Options $options) => array_values($this->loadPermissions($options)),
        ]);
        $resolver->setAllowedValues('mapped', [false]);
        $resolver->setAllowedTypes('resource_domain', [AbstractResourceDomain::class]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['resource_domain'] = $options['resource_domain'];
    }

    protected function loadPermissions(Options $options): array
    {
        return $this->permissionManager->findPermissions(
            $options['resource_domain'],
            $options['resource'],
            alwaysAddPublicAccess: $options['show_public_access'],
        );
    }

}
