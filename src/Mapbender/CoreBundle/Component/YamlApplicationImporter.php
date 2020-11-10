<?php


namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class YamlApplicationImporter
{
    /** @var MutableAclProviderInterface */
    protected $aclProvider;

    /**
     * @param MutableAclProviderInterface $aclProvider
     */
    public function __construct(MutableAclProviderInterface $aclProvider)
    {
        $this->aclProvider = $aclProvider;
    }

    public function addViewPermissions(Entity\Application $application)
    {
        $maskBuilder       = new MaskBuilder();
        $uoid              = ObjectIdentity::fromDomainObject($application);

        $maskBuilder->add('VIEW');

        try {
            $acl = $this->aclProvider->findAcl($uoid);
        } catch (\Exception $e) {
            $acl = $this->aclProvider->createAcl($uoid);
        }

        $acl->insertObjectAce(new RoleSecurityIdentity('IS_AUTHENTICATED_ANONYMOUSLY'), $maskBuilder->get());
        $this->aclProvider->updateAcl($acl);
    }
}
