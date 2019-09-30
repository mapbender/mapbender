<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AclExtension extends AbstractExtension
{
    /** @var AclProviderInterface */
    protected $provider;

    /**
     * @param AclProviderInterface $provider
     */
    public function __construct(AclProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getFunctions()
    {
        return array(
            'mapbender_manager_object_aces' => new TwigFunction('mapbender_manager_object_aces', array($this, 'mapbender_manager_object_aces')),
        );
    }

    /**
     * @param object $object
     * @return string[][]
     */
    public function mapbender_manager_object_aces($object)
    {
        $acl = $this->getAcl($object);
        $data = array();
        if ($acl) {
            /** @var AclInterface $acl */
            foreach ($acl->getObjectAces() as $entry) {
                /** @var EntryInterface $entry */
                $identity = $entry->getSecurityIdentity();
                if ($identity instanceof UserSecurityIdentity) {
                    $data[] = array(
                        'name' => $identity->getUsername(),
                        'type' => 'User',
                    );
                } elseif ($identity instanceof RoleSecurityIdentity) {
                    $data[] = array(
                        'name' => $identity->getRole(),
                        'type' => 'Role',
                    );
                }
            }
        }
        return $data;
    }

    /**
     * @param string|object $objectOrString
     * @return \SplObjectStorage|AclInterface|null
     */
    protected function getAcl($objectOrString)
    {
        if (is_string($objectOrString)) {
            $oid = new ObjectIdentity('class', $objectOrString);
        } else {
            $oid = ObjectIdentity::fromDomainObject($objectOrString);
        }
        try {
            return $this->provider->findAcl($oid);
        } catch (NotAllAclsFoundException $e) {
            return $e->getPartialResult();
        } catch (AclNotFoundException $e) {
            return null;
        }
    }
}
