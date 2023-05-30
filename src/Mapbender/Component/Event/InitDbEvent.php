<?php

namespace Mapbender\Component\Event;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Miniature version of Console\CommandEvent, without providing the Command object nor
 * the Input, just the Output for writing.
 */
class InitDbEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /** @var OutputInterface */
    protected $output;

    /**
     * @param OutputInterface|null $output
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
