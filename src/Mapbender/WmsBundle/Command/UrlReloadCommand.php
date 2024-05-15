<?php


namespace Mapbender\WmsBundle\Command;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mapbender:wms:reload:url')]
class UrlReloadCommand extends AbstractHttpCapabilitiesProcessingCommand
{
    protected function configure(): void
    {
        $this
            ->setDescription('Reloads a WMS source from given url')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of the source')
            ->addOption(self::OPTION_DEACTIVATE_NEW_LAYERS, null, InputOption::VALUE_NONE, 'If set, newly added layers will be deactivated in existing instances. Deactivated layers are not visible in the frontend.')
            ->addOption(self::OPTION_DESELECT_NEW_LAYERS, null, InputOption::VALUE_NONE, 'If set, newly added layers will be deselected in existing instances. Deselected layers are not visible on the map by default, but appear in the layer tree and can be selected by users.')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
            $output->writeln("Updated source #$targetId");
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        return 0;
    }

    protected function getValidationOption(InputInterface $input)
    {
        return $input->getOption('validate');
    }
}
