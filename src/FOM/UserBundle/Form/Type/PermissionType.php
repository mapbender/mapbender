<?php

namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\TagboxType;
use FOM\UserBundle\Security\Permission\AbstractAttributeDomain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionType extends AbstractType
{
    public function __construct(private DataTransformerInterface $modelTransformer)
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
        $builder->addModelTransformer($this->modelTransformer);

        $builder->add('sid', HiddenType::class, [
            'required' => true,
            'label' => false,
            'attr' => array(
                'autocomplete' => 'off',
                'readonly' => true,
            ),
        ]);


        /** @var AbstractAttributeDomain $attribute_domain */
        $attribute_domain = $options['attribute_domain'];
        $i = 0;
        // TODO: start with 0
        foreach ($attribute_domain->getPermissions() as $permission) {
            $builder
                ->add('permission_' . $i, TagboxType::class, [
                    'property_path' => '[permissions][' . ($i+1) . ']',
                    'attr' => ['class' => $permission],
                ])
            ;
            $i++;
        }
    }

}
