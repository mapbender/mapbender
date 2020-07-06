<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCapabilitiesProcessingCommand extends AbstractSourceCommand
{
    protected function processSource(OutputInterface $output, WmsSource $source)
    {
        // do nothing
    }

    abstract protected function getValidationOption(InputInterface $input);
}
