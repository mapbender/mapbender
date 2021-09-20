<?php


namespace Mapbender\PrintBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueRepairCommand extends AbstractPrintQueueCommand
{
    protected function configure()
    {
        $this
            ->setDescription("Reset hung / crashed queued print jobs so they can be executed again")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Resetting hung jobs");
        $atLeastOne = false;
        foreach ($this->repository->findHung() as $entity) {
            $entity->setStarted(null);
            $this->entityManager->persist($entity);

            $output->writeln("Reset hung job #{$entity->getId()}");
            $atLeastOne = true;
        }
        if ($atLeastOne) {
            $this->entityManager->flush();
        } else {
            $output->writeln("No hung jobs to reset");
        }
    }
}
