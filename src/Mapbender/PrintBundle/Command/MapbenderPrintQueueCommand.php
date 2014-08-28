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
    const DESCRIPTION = 'Print queue managing';

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
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PrintQueueManager $manager */
        /** @var PrintQueue $queue */
        $manager = $this->getContainer()->get('mapbender.print.queue_manager');
        $result  = null;

        if ($input->hasOption('clean')) {
            $output->writeln('Cleaned process started.');
            $output->writeln(sprintf('Cleaned %s queue(s) successfully.', count($manager->clean())));
            return;
        } elseif ($input->hasArgument('id')) {
            $queue = $manager->find($input->getArgument('id'));
            if ($queue) {
                $result = $manager->render($queue);
            } else {
                $result = PrintQueueManager::STATUS_QUEUE_NOT_EXISTS;
            }
        } else {
            $queue = $manager->renderNext();
        }

        switch ($result) {
            case PrintQueueManager::STATUS_QUEUE_NOT_EXISTS:
                $output->writeln('Queue with the given id doesn`t exists.');
                break;

            case PrintQueueManager::STATUS_WRONG_QUEUED:
                $output->writeln('The queue status as if already in the rendering.
                Maybe "started" timestamp  saved wrong.
                Check database entries or try again later.'
                );
                break;

            case PrintQueueManager::STATUS_IN_PROCESS:
                $output->writeln('The queue is already in process. Try again later.');
                break;

            case PrintQueueManager::STATUS_QUEUE_EMPTY:
                $output->writeln('The queue is empty. Try again later.');
                break;

            default:
                $output->writeln('New PDF is rendered successfully:\n' . $manager->getPdFilePath($queue));
        }
    }
} 