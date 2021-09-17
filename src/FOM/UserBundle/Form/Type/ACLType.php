<?php
namespace FOM\UserBundle\Form\Type;

use FOM\ManagerBundle\Form\Type\BaseAclType;
use FOM\UserBundle\Form\DataTransformer\ACEDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;


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
            'object_identity' => null,
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
        $resolver->setAllowedTypes('object_identity', array(
            'null',
            'Symfony\Component\Security\Acl\Model\ObjectIdentityInterface',
        ));
    }

    protected function loadAces($options)
    {
        $acl = $this->aclProvider->findAcl($options['object_identity']);
        return $acl->getObjectAces();
    }

    /**
     * @param array $options
     * @return EntryInterface[]|array[] for array type, each entry has keys 'mask' (integer bit mask) and 'sid' (SecurityIdentityInterface)
     * @todo: fix inconsistent value types. These are currently fixed at the ACE level
     * @see ACEDataTransformer
     */
    protected function getAces($options)
    {
        if (!empty($options['object_identity'])) {
            try {
                return $this->loadAces($options);
            } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
                // fall through
            }
        }
        return array();
    }
}
