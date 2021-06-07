<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class ApplicationCloneCommand extends AbstractApplicationTransportCommand
{
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(EntityManagerInterface $defaultEntityManager,
                                ImportHandler $importHandler,
                                ApplicationYAMLMapper $yamlRepository)
    {
        parent::__construct($defaultEntityManager, $importHandler);
        $this->yamlRepository = $yamlRepository;
    }

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
