<?php

namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\TagboxType;
use FOM\UserBundle\Form\DataTransformer\PermissionDataTransformer;
use FOM\UserBundle\Security\Permission\AbstractAttributeDomain;
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
            'attribute_domain' => null
        ]);
        $resolver->setAllowedTypes('attribute_domain', [AbstractAttributeDomain::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new PermissionDataTransformer(
                $options["attribute_domain"],
                $this->permissionManager
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

        /** @var AbstractAttributeDomain $attribute_domain */
        $attribute_domain = $options['attribute_domain'];
        $i = 0;
        foreach ($attribute_domain->getPermissions() as $permission) {
            $class = $attribute_domain->getCssClassForPermission($permission);
            $builder
                ->add('permission_' . $i, TagboxType::class, [
                    'property_path' => '[permissions][' . $i . ']',
                    'attr' => [
                        'class' => $class,
                        'data-permission-name' => $permission
                    ],
                ])
            ;
            $i++;
        }
    }

}
