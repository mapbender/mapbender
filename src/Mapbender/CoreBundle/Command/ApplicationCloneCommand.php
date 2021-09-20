<?php


namespace Mapbender\CoreBundle\Command;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class ApplicationCloneCommand extends AbstractApplicationTransportCommand
{
    protected function configure()
    {
        $this->setName('mapbender:application:clone');
        $this->addArgument('slug', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
            $importHandler->addOwner($application, UserSecurityIdentity::fromAccount($root));
        }

        $output->writeln("Application cloned to new slug {$clonedApp->getSlug()}, id {$clonedApp->getId()}");
    }
}
