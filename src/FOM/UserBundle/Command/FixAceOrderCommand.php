<?php

namespace FOM\UserBundle\Command;

use FOM\UserBundle\Service\FixAceOrderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixAceOrderCommand extends Command
{
    const COMMAND = 'mapbender:security:fixacl';

    private FixAceOrderService $fixAceOrderService;

    public function __construct(FixAceOrderService $fixAceOrderService)
    {
        parent::__construct(self::COMMAND);
        $this->fixAceOrderService = $fixAceOrderService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Fixes the indices of access control lists')
            ->setHelp(<<<EOT
The (deprecated) symfony/acl-bundle has a bug that prevents an access control list (ACL) from being saved,
when a security identify (e.g. a user) was deleted that had an entry in the component's ACL and was not the
last one in the list. The ACL expects the ace_order column to start with zero and increase one by one,
otherwise array indexing fails.
For example: An application has access rights for user A, B and C. User B is deleted. Then, the acl_entries
table will have only entries for users A (ace_order 0) and C (ace_order 2) which can't be saved anymore
This command goes through the whole acl_entries table and resets the indices of entries where the ace_order
contains gaps.
EOT
            )
            ->setName(self::COMMAND)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fixAceOrderService->fixAceOrder();
        $output->writeln("Command completed.");
        return null;
    }
}
