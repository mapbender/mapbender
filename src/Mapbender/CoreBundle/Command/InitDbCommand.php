<?php


namespace Mapbender\CoreBundle\Command;


use Mapbender\Component\Event\InitDbEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * This command emits the event 'mapbender.init.db' that can be subscribed on to perform required database
 * (re-)initializations and cleanups. Known mapbender core subscribers are in the Mapbender\CoreBundle\EventHandler\InitDb namespace.
 */
class InitDbCommand extends Command
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct('mapbender:database:init');
    }

    protected function configure(): void
    {
        $this->setDescription('Performs required db (re-)initializations and cleanups');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $event = new InitDbEvent($output);
        $this->eventDispatcher->dispatch($event, 'mapbender.init.db');
        return 0;
    }
}
