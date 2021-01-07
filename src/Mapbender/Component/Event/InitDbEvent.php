<?php

namespace Mapbender\Component\Event;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Miniature version of Console\CommandEvent, without providing the Command object nor
 * the Input, just the Output for writing.
 */
class InitDbEvent extends Event
{
    /** @var OutputInterface */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output = null)
    {
        $this->output = $output ?: new NullOutput();
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}
