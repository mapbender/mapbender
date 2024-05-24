<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractHttpCapabilitiesProcessingCommand extends AbstractCapabilitiesProcessingCommand
{
    public const OPTION_DESELECT_NEW_LAYERS = 'deselect-new-layers';
    public const OPTION_DEACTIVATE_NEW_LAYERS = 'deactivate-new-layers';

    protected function configure(): void
    {
        $this
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Run xml schema validation (slow)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $origin = $this->getOrigin($input);
        $this->processOrigin($origin, $input);
        $source = $this->loadSource($origin);
        $msg = 'WMS source loaded';
        if ($this->getValidationOption($input)) {
            $msg .= ' and validated';
        }
        $output->writeln($msg, OutputInterface::VERBOSITY_VERBOSE);
        $this->processSource($output, $source);
        return 0;
    }

    protected function getOrigin(InputInterface $input)
    {
        $origin = new HttpOriginModel();
        $origin->setOriginUrl($input->getArgument('serviceUrl'));
        $origin->setUsername($input->getOption('user'));
        $origin->setPassword($input->getOption('password'));

        if ($input->hasOption(self::OPTION_DESELECT_NEW_LAYERS) && $input->getOption(self::OPTION_DESELECT_NEW_LAYERS)) {
            $origin->setSelectNewLayers(false);
        }
        if ($input->hasOption(self::OPTION_DEACTIVATE_NEW_LAYERS) && $input->getOption(self::OPTION_DEACTIVATE_NEW_LAYERS)) {
            $origin->setActivateNewLayers(false);
        }
        return $origin;
    }

    /**
     * @param HttpOriginModel $origin
     * @return WmsSource
     */
    protected function loadSource(HttpOriginModel $origin)
    {
        return $this->getImporter()->evaluateServer($origin);
    }

    protected function processOrigin(HttpOriginModel $origin, InputInterface $input)
    {
        if ($this->getValidationOption($input)) {
            $this->getImporter()->validateServer($origin);
        }
    }
}
