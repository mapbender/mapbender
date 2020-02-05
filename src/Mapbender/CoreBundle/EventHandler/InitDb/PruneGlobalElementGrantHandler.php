<?php


namespace Mapbender\CoreBundle\EventHandler\InitDb;


use Mapbender\Component\Event\AbstractInitDbHandler;
use Mapbender\Component\Event\InitDbEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class PruneGlobalElementGrantHandler extends AbstractInitDbHandler
{
    /** @var AclProviderInterface|MutableAclProviderInterface|null */
    protected $aclProvider;

    /**
     * @param AclProviderInterface|null $aclProvider
     */
    public function __construct(AclProviderInterface $aclProvider = null)
    {
        $this->aclProvider = $aclProvider;
    }

    public function onInitDb(InitDbEvent $event)
    {
        $output = $event->getOutput();
        if ($this->aclProvider) {
            $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Element');
            try {
                $acl = $this->aclProvider->findAcl($oid);
                if ($acl->getClassAces() && ($acl instanceof MutableAclInterface)) {
                    $this->clearGlobalGrants($acl, $output);
                } else {
                    $output->writeln("{$oid->getType()} has no assigned mutable global grants, nothing to do", OutputInterface::VERBOSITY_VERBOSE);
                }
            } catch (AclNotFoundException $e) {
                $output->writeln("{$oid->getType()} has no assigned global grants, nothing to do", OutputInterface::VERBOSITY_VERBOSE);
            }
        } else {
            $output->writeln("Skipping acl update, no provider", OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    protected function clearGlobalGrants(MutableAclInterface $acl, OutputInterface $output)
    {
        $classAces = $acl->getClassAces();
        $output->writeln("{$acl->getObjectIdentity()->getType()} has " . count($classAces) . " assigned global grants", OutputInterface::VERBOSITY_VERBOSE);
        // drop class aces in reverse order to minimize the amount of acl property change events
        foreach (array_reverse(array_keys($classAces)) as $aceIndex) {
            /** @var Entry $ace */
            $ace = $classAces[$aceIndex];
            $output->writeln("* dropping global grant to {$ace->getSecurityIdentity()} / mask {$ace->getMask()}", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $acl->deleteClassAce($aceIndex);
        }
        $this->aclProvider->updateAcl($acl);
    }
}
