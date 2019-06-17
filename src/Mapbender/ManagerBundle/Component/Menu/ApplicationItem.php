<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationItem extends MenuItem
{
    /** @var ObjectIdentity */
    protected $oid;

    public function __construct($title, $route)
    {
        parent::__construct($title, $route);
        $this->oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
    }

    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        return $authorizationChecker->isGranted('CREATE', $this->oid);
    }
}
