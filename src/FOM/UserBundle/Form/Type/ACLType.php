<?php
namespace FOM\UserBundle\Form\Type;

use FOM\UserBundle\Form\DataTransformer\ACEDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


/**
 * Form type for editing / assigning object ACLs.
 *
 * Available permission sets can be passed in either as an explicit array
 * of mask bit position => label, or with the magic legacy string value
 *'standard::object'.
 *
 * @author Christian Wygoda
 */
class ACLType extends AbstractType
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var AclProviderInterface */
    protected $aclProvider;

    /**
     * ACLType constructor.
     *
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

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'acl';
    }

    public function getBlockPrefix()
    {
        return 'acl';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'permissions' => array(),
            // Can never be mapped. Retrieval and storage goes through MutableAclProvider.
            // Default added post 3.1.11 / 3.2.11. For BC with older FOM, external users should
            // pass in mapped = false redundantly.
            'mapped' => false,
            'create_standard_permissions' => true,
            'standard_anon_access' => null,
            'aces' => null,
        ));
        $resolver->setAllowedValues('mapped', array(false));
    }

    protected function loadAces($options)
    {
        $oid = ObjectIdentity::fromDomainObject($options['data']);
        $acl = $this->aclProvider->findAcl($oid);
        return $acl->getObjectAces();
    }

    /**
     * Creates some default ACEs for a newly created ACL.
     *
     * @param $options
     * @return array[]
     */
    protected function buildAces($options)
    {
        $aces = array();
        if ($options['create_standard_permissions']) {
            // for unsaved entities, fake three standard permissions:
            // - Owner access for current user
            // - View access for anonymous users
            // - View access for logged in users
            $aces = array();

            $token = $this->tokenStorage->getToken();
            if ($token) {
                $ownerAccess = array(
                    'sid' => UserSecurityIdentity::fromToken($token),
                    'mask' => MaskBuilder::MASK_OWNER,
                );
                $aces[] = $ownerAccess;
            }
        }

        // only used in Mapbender ApplicationType: grant view to anonymous users.
        // @todo: This is redundant because a) Applications have an entire dedicated database column 'published' to
        //        control anonymous view and b) edit / delete / owner etc should never be conceivably be granted
        //        to an anonymous user on any concrete object or oid.
        //        Remove this entire clause after resolving yaml-vs-db inconsistencies of application grants setups
        //        in Mapbender.
        if ($options['standard_anon_access'] || ($options['standard_anon_access'] === null && $options['create_standard_permissions'])) {
            $anon = new RoleSecurityIdentity('IS_AUTHENTICATED_ANONYMOUSLY');
            $aces[] = array(
                'sid' => $anon,
                'mask' => MaskBuilder::MASK_VIEW,
            );
        }
        return $aces;
    }

    /**
     * @param array $options
     * @return EntryInterface[]|array[] for array type, each entry has keys 'mask' (integer bit mask) and 'sid' (SecurityIdentityInterface)
     * @todo: fix inconsistent value types. These are currently fixed at the ACE level
     * @see ACEDataTransformer
     */
    protected function getAces(array $options)
    {
        if (is_array($options['aces'])) {
            return $options['aces'];
        } else {
            try {
                return $this->loadAces($options);
            } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
                return $this->buildAces($options);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $aceOptions = array(
            'entry_type' => 'FOM\UserBundle\Form\Type\ACEType',
            'label' => 'Permissions',
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'entry_options' => array(
                'available_permissions' => $this->getPermissions($options),
            ),
            'mapped' => false,
            'data' => $this->getAces($options),
        );

        $builder->add('ace', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', $aceOptions);
    }

    /**
     * @param array $options
     * @return string[] with labels mapped to ACE mask bit positions
     * @see MaskBuilder constants
     */
    protected function getPermissions(array $options)
    {
        if ($options['permissions'] === 'standard::object') {
            return array(
                1 => 'View',
                3 => 'Edit',
                4 => 'Delete',
                6 => 'Operator',
                7 => 'Master',
                8 => 'Owner',
            );
        } elseif (is_array($options['permissions'])) {
            return $options['permissions'];
        } else {
            throw new \InvalidArgumentException("Unsupported 'permissions' option " . print_r($options['permissions'], true));
        }
    }
}
