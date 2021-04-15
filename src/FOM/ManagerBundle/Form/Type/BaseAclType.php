<?php


namespace FOM\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BaseAclType extends AbstractType
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var AclProviderInterface */
    protected $aclProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AclProviderInterface $aclProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AclProviderInterface $aclProvider)
    {
        $this->tokenStorage = $tokenStorage;
        $this->aclProvider = $aclProvider;
    }

    public function getBlockPrefix()
    {
        return 'acl';
    }

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
    }

    abstract protected function getAces(array $options);

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $aceOptions = array(
            'entry_type' => 'FOM\UserBundle\Form\Type\ACEType',
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'entry_options' => $options['entry_options'],
            'mapped' => false,
            'data' => $this->getAces($options),
        );

        $builder->add('ace', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', $aceOptions);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_add'] = $options['allow_add'];
    }
}
