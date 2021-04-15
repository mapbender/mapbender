<?php


namespace FOM\ManagerBundle\Form\Type;


use FOM\UserBundle\Form\Type\ACLType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;

/**
 * Type for assigning / editing grants on entire ObjectIdentity classes.
 * Adds the create permission only relevant for OIDs.
 */
class ClassAclType extends ACLType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(array(
           'class',
        ));
        $resolver->setAllowedTypes('class', array('string'));
        // lock away inherited options. @todo: extract common base class without these
        $resolver->setDefaults(array(
            'create_standard_permissions' => false,
            'standard_anon_access' => false,
            'permissions' => false,
        ));
        $resolver->setAllowedValues('create_standard_permissions', array(false));
        $resolver->setAllowedValues('standard_anon_access', array(false));
        $resolver->setAllowedValues('permissions', array(false));
    }

    /**
     * @param array $options
     * @return EntryInterface[]
     */
    protected function getAces(array $options)
    {
        $oid = new ObjectIdentity('class', $options['class']);

        try {
            $acl = $this->aclProvider->findAcl($oid);
            return $acl->getClassAces();
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            return array();
        }
    }

    /**
     * @param array $options
     * @return string[]
     */
    protected function getPermissions(array $options)
    {
        return array(
            1 => 'View',
            2 => 'Create',
            3 => 'Edit',
            4 => 'Delete',
            6 => 'Operator',
            7 => 'Master',
            8 => 'Owner',
        );
    }
}
