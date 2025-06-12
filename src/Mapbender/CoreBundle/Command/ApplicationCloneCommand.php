<?php


namespace Mapbender\CoreBundle\Command;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mapbender:application:clone')]
class ApplicationCloneCommand extends AbstractApplicationTransportCommand
{
    protected function configure(): void
    {
        $this->addArgument('slug', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug = $input->getArgument('slug');
        /** @var Application|null $application */
        $application = $this->getApplicationRepository()->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application) {
            $application = $this->yamlRepository->getApplication($slug);
        }
        if (!$application) {
            throw new \RuntimeException("No application with slug {$slug}");
        }

        $importHandler = $this->getApplicationImporter();
        $clonedApp = $importHandler->duplicateApplication($application);
        if ($root = $this->getRootUser()) {
            $importHandler->addOwner($clonedApp, $root);
        }

        $output->writeln("Application cloned to new slug {$clonedApp->getSlug()}, id {$clonedApp->getId()}");
        return 0;
    }
}
