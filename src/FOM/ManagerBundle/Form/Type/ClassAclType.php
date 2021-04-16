<?php


namespace FOM\ManagerBundle\Form\Type;


use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;

/**
 * Type for assigning / editing grants on entire ObjectIdentity classes.
 * Adds the create permission only relevant for OIDs.
 */
class ClassAclType extends BaseAclType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(array(
           'class',
        ));
        $resolver->setAllowedTypes('class', array('string'));
    }

    /**
     * @param array $options
     * @return EntryInterface[]
     */
    protected function getAces($options)
    {
        $oid = new ObjectIdentity('class', $options['class']);

        try {
            $acl = $this->aclProvider->findAcl($oid);
            return $acl->getClassAces();
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            return array();
        }
    }
}
