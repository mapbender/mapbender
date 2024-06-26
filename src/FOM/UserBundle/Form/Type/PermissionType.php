<?php

namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\TagboxType;
use FOM\UserBundle\Form\DataTransformer\PermissionDataTransformer;
use FOM\UserBundle\Security\Permission\AbstractResourceDomain;
use FOM\UserBundle\Security\Permission\PermissionManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionType extends AbstractType
{
    public function __construct(private PermissionManager $permissionManager)
    {
    }

    public function getBlockPrefix(): string
    {
        return "permission";
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'resource_domain' => null,
            'action_filter' => null,
        ]);
        $resolver->setAllowedTypes('resource_domain', [AbstractResourceDomain::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AbstractResourceDomain $resourceDomain */
        $resourceDomain = $options['resource_domain'];
        $availableActions = is_array($options['action_filter']) ? $options['action_filter'] : $resourceDomain->getActions();

        $builder->addModelTransformer(new PermissionDataTransformer(
                $this->permissionManager,
                $availableActions
            )
        );

        $hiddenOptions = [
            'required' => true,
            'label' => false,
            'attr' => array(
                'autocomplete' => 'off',
                'readonly' => true,
            ),
        ];
        $builder->add('icon', HiddenType::class, $hiddenOptions);
        $builder->add('title', HiddenType::class, $hiddenOptions);
        $builder->add('subjectJson', HiddenType::class, $hiddenOptions);

        $i = 0;
        foreach ($availableActions as $action) {
            $class = $resourceDomain->getCssClassForAction($action);
            $builder
                ->add('permission_' . $i, TagboxType::class, [
                    'property_path' => '[permissions][' . $i . ']',
                    'attr' => [
                        'class' => $class,
                        'data-action-name' => $action
                    ],
                    'translation_prefix' => $resourceDomain->getTranslationPrefix(),
                ])
            ;
            $i++;
        }
    }

}
