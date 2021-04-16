<?php
namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\BaseAclType;
use FOM\UserBundle\Form\DataTransformer\ACEDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;


/**
 * Form type for editing / assigning object ACLs.
 *
 * Available permission sets can be passed in either as an explicit array
 * of mask bit position => label, or with the magic legacy string value
 *'standard::object'.
 *
 * @author Christian Wygoda
 */
class ACLType extends BaseAclType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'create_standard_permissions' => true,
            'entry_options' => array(
                'mask' => array_sum(array(
                    // Same as ACEType default, minus MASK_CREATE
                    MaskBuilder::MASK_VIEW,
                    MaskBuilder::MASK_EDIT,
                    MaskBuilder::MASK_DELETE,
                    MaskBuilder::MASK_OPERATOR,
                    MaskBuilder::MASK_MASTER,
                    MaskBuilder::MASK_OWNER,
                )),
            ),
        ));
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
        try {
            return $this->loadAces($options);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            return $this->buildAces($options);
        }
    }
}
