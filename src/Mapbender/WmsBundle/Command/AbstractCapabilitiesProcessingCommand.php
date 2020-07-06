<?php


namespace Mapbender\WmsBundle\Command;


use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractCapabilitiesProcessingCommand extends AbstractSourceCommand
{
    abstract protected function getValidationOption(InputInterface $input);
}
