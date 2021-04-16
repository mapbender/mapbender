<?php


namespace FOM\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BaseAclType extends AbstractType
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var MutableAclProviderInterface */
    protected $aclProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param MutableAclProviderInterface $aclProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        MutableAclProviderInterface $aclProvider)
    {
        $this->tokenStorage = $tokenStorage;
        $this->aclProvider = $aclProvider;
    }

    public function getBlockPrefix()
    {
        return 'acl';
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\CollectionType';
    }

    abstract protected function getAces($options);

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // Can never be mapped. Retrieval and storage goes through MutableAclProvider.
            // Default added post 3.1.11 / 3.2.11. For BC with older FOM, external users should
            // pass in mapped = false redundantly.
            'mapped' => false,
            'allow_add' => true,
            'entry_options' => array(),
        ));
        $resolver->setAllowedValues('mapped', array(false));
        $type = $this;
        $resolver->setDefaults(array(
            'entry_type' => 'FOM\UserBundle\Form\Type\ACEType',
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'mapped' => false,
            'data' => function (Options $options) use ($type) {
                return $type->getAces($options);
            },
        ));
    }
}
