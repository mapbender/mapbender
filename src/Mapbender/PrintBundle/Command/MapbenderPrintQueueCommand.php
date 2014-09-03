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
            $this->pass('Cleaned process started.');
            $this->pass(sprintf('Cleaned %s queue(s) successfully.', count($manager->clean())));
            return;
        }
        if ($input->getOption('repair')) {
            $this->pass('Fixing broken queues.');
            foreach($manager->fixBroken() as $queue){
                $this->pass('The queue #'.$queue->getId()." fixed.");
            }
        }

        if ($id > 0) {
            $queue = $manager->find($id);
            if ($queue) {
                $this->pass('The queue founded.');
                $this->pass('Start processing.');
                $result = $manager->render($queue,true);

            } else {
                $result = PrintQueueManager::STATUS_QUEUE_NOT_EXISTS;
            }
        } else {
            $renderedQueue = $manager->getProcessedQueue();
            if($renderedQueue){
                $this->warn('The queue #'.$renderedQueue->getId().' is not successfully rendered yet. Run this command with --repair option to fix this.');
            }

            $this->pass('Get next queue.');
            $queue  = $manager->getNextQueue();
            if($queue instanceof PrintQueue){
                $this->pass('New queue #'.$queue->getId().' processing.');
                $manager->render($queue);
            }else{
                $result = PrintQueueManager::STATUS_QUEUE_EMPTY;
            }
        }

        switch ($result) {
            case PrintQueueManager::STATUS_QUEUE_NOT_EXISTS:
                $this->error('The queue doesn`t exists.');
                $this->warn(self::NOTHING_DONE_TEXT);
                break;

            case PrintQueueManager::STATUS_WRONG_QUEUED:
                $this->warn('The queue is already rendered.');
                $this->warn(self::NOTHING_DONE_TEXT);
                break;

            case PrintQueueManager::STATUS_IN_PROCESS:
                $this->warn('The queue is already in process.');
                $this->warn(self::NOTHING_DONE_TEXT);
                break;

            case PrintQueueManager::STATUS_QUEUE_EMPTY:
                $this->pass('The queue is empty.');
                $this->warn(self::NOTHING_DONE_TEXT);
                break;

            default:
                $this->pass('PDF rendered successfully to: ' . self::PASS_COLOR .realpath($manager->getPdfPath($queue)). self::COLOR_OFF);
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