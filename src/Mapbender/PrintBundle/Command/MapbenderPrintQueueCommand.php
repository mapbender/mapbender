<?php

namespace Mapbender\PrintBundle\Command;

use Mapbender\PrintBundle\Component\PrintQueueManager;
use Mapbender\PrintBundle\Entity\PrintQueue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MapbenderPrintQueueCommand
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class MapbenderPrintQueueCommand extends ContainerAwareCommand
{
    const DESCRIPTION       = 'Print queue managing';
    const COLOR_OFF         = "\033[0m";
    const WARN_COLOR        = "\033[0;33m";
    const PASS_COLOR        = "\033[0;32m";
    const ERROR_COLOR       = "\033[0;31m";
    const NOTHING_DONE_TEXT = 'Nothing done.';

    /** @var  OutputInterface */
    protected $output;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription(self::DESCRIPTION)
            ->setHelp(self::DESCRIPTION)
            ->setName('mapbender:print:queue')
            ->addArgument('id', InputArgument::OPTIONAL, 'Queue ID for which rendering should be started.')
            ->addOption('clean',
                null,
                InputOption::VALUE_NONE,
                'Clean old queues and remove depended files they are older then X days.'
            )
            ->addOption('repair',
                null,
                InputOption::VALUE_NONE,
                'Fix broken queues.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PrintQueueManager $manager */
        /** @var PrintQueue $queue */
        $manager      = $this->getContainer()->get('mapbender.print.queue_manager');
        $result       = null;
        $id           = intval($input->getArgument('id'));
        $this->output = $output;

        if ($input->getOption('clean')) {
            $this->pass('Print queue clean process started.');
            $this->pass(sprintf('Cleaned %s print queue item(s) successfully.', count($manager->clean())));
            return;
        }
        if ($input->getOption('repair')) {
            $this->pass('Fixing broken items on print queue.');
            $fixedQueues = $manager->fixBroken();
            if ($fixedQueues) foreach ($fixedQueues as $queue) {
                $this->pass('The queue id #'.$queue->getId()." fixed.");
            } else {
                $this->pass("No broken print queue items found for fixing");
            }
        }

        if ($id > 0) {
            $queue = $manager->find($id);
            if ($queue) {
                $this->pass("Starting processing of print queue id #{$id}");
                $result = $manager->render($queue,true);
            } else {
                $this->error("No print queue with id $id");
            }
        } else {
            $renderedQueue = $manager->getProcessedQueue();
            if($renderedQueue){
                $this->warn('The queue #'.$renderedQueue->getId().' is not successfully rendered yet. Run this command with --repair option to fix this.');
            }
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->pass('Get next queue.');
            }
            $queue  = $manager->getNextQueue();
            if($queue instanceof PrintQueue){
                $this->pass('New queue #'.$queue->getId().' processing.');
                $result = $manager->render($queue);
            }else{
                $result = PrintQueueManager::STATUS_QUEUE_EMPTY;
            }
        }

        switch ($result) {
            case PrintQueueManager::STATUS_RENDERING_SAVE_ERROR:
                $this->error("Print queue id #{$queue->getId()} could not be saved");
                break;
            case PrintQueueManager::STATUS_WRONG_QUEUED:
                $this->warn("Print queue id #{$queue->getId()} is already rendered.");
                break;
            case PrintQueueManager::STATUS_IN_PROCESS:
                $this->warn("Print queue id #{$queue->getId()} already in process.");
                break;
            case PrintQueueManager::STATUS_QUEUE_EMPTY:
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $this->pass('The print queue is empty.');
                }
                break;
            default:
                if ($result instanceof PrintQueue) {
                    $this->pass("PDF for print queue id #{$queue->getId()} rendered successfully to: "
                                . realpath($manager->getPdfPath($queue)));
                } elseif ($result !== null) {
                    $this->warn("Unhandled PrintQueueManager result $result");
                }

        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->pass('Opened queues: '.$manager->countOpenedQueues());
        }
    }

    protected function pass($message)
    {
        $this->writeMessage('PASS', self::PASS_COLOR, $message);
    }

    protected function warn($message)
    {
        $this->writeMessage('WARN', self::WARN_COLOR, $message);
    }

    protected function error($message)
    {
        $this->writeMessage('ERROR', self::ERROR_COLOR, $message);
    }

    protected function writeMessage($title, $color, $message)
    {
        $this->output->writeln($color . '[' . $title . ']' . self::COLOR_OFF . ' ' . $message);
    }
}