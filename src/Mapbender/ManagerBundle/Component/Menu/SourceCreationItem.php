<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SourceCreationItem extends MenuItem
{
    /** @var ObjectIdentity */
    protected $oid;

    public function __construct()
    {
        parent::__construct('mb.manager.managerbundle.add_source', 'mapbender_manager_repository_new');
        $this->oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
    }

    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        return $authorizationChecker->isGranted('CREATE', $this->oid);
    }
}
