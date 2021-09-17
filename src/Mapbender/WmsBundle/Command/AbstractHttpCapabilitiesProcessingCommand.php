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
    protected function configure()
    {
        $this
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Run xml schema validation (slow)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
    }

    protected function getOrigin(InputInterface $input)
    {
        $origin = new HttpOriginModel();
        $origin->setOriginUrl($input->getArgument('serviceUrl'));
        $origin->setUsername($input->getOption('user'));
        $origin->setPassword($input->getOption('password'));
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
