<?php

namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\TagboxType;
use FOM\UserBundle\Security\Permission\AbstractAttributeDomain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionType extends AbstractType
{
    public function __construct(private DataTransformerInterface $modelTransformer)
    {
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
        $builder->addModelTransformer($this->modelTransformer);

        /** @var AbstractAttributeDomain $attribute_domain */
        $attribute_domain = $options['attribute_domain'];
        foreach ($attribute_domain->getPermissions() as $permission) {
            $builder
                ->add('permission_' . $permission, TagboxType::class, [
                    'property_path' => '[permissions][' . $permission . ']',
                    'attr' => ['class' => $attribute_domain->getSlug()],
                ])
            ;
        }
    }

}
