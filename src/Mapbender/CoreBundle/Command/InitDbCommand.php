<?php


namespace Mapbender\CoreBundle\Command;


use Mapbender\Component\Event\InitDbEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @todo: absorb EPSG importing / updating
 * @todo: fix invalid database content artifacts (e.g. WMS sources with multiple root layers)
 * DO NOT absorb irreversible config pruning from mapbender:database:upgrade, keep that separate
 *
 * @since v3.0.8.5
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

    protected function configure()
    {
        $this->setDescription('Performs required db (re-)initializations and cleanups');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $event = new InitDbEvent($output);
        $this->eventDispatcher->dispatch($event, 'mapbender.init.db');
    }
}
