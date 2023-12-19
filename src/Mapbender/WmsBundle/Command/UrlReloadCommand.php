<?php


namespace Mapbender\WmsBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UrlReloadCommand extends AbstractHttpCapabilitiesProcessingCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:wms:reload:url')
            ->setDescription('Reloads a WMS source from given url')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of the source')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Run xml schema validation (slow)')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Run xml schema validation (slow)')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetId = $input->getArgument('id');
        $target = $this->getSourceById($targetId);
        $origin = $this->getOrigin($input);
        $this->processOrigin($origin, $input);
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $this->getImporter()->refresh($target, $origin);
            $em->persist($target);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    protected function getValidationOption(InputInterface $input)
    {
        return $input->getOption('validate');
    }
}
