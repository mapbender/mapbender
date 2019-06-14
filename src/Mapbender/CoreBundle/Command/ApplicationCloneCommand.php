<?php


namespace Mapbender\CoreBundle\Command;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            $application = $this->getYamlApplication($slug);
            if ($application) {
                // Avoid saving an application to the db with the same slug
                // as the Yaml version. There's a unique constraint on the
                // database table, but it doesn't account for Yaml-defined
                // applications!
                $application->setSlug($application->getSlug() . '_db');
            }
        }
        if (!$application) {
            throw new \RuntimeException("No application with slug {$slug}");
        }

        $importHandler = $this->getApplicationImporter();
        $newApplications = $importHandler->duplicateApplication($application);
        $clonedApp = $newApplications[0];
        $output->writeln("Application cloned to new slug {$clonedApp->getSlug()}, id {$clonedApp->getId()}");
    }

    /**
     * @param string $slug
     * @return Application|null
     */
    protected function getYamlApplication($slug)
    {
        /** @var Mapbender $m */
        $m = $this->getContainer()->get('mapbender');
        $apps = $m->getYamlApplicationEntities();
        return ArrayUtil::getDefault($apps, $slug, null);
    }
}
