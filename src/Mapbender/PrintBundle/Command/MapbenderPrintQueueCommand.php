<?php

namespace Mapbender\PrintBundle\Command;

use Mapbender\PrintBundle\Component\PrintQueueManager;
use Mapbender\PrintBundle\DependencyInjection\MapbenderPrintExtension;
use Mapbender\PrintBundle\Entity\PrintQueue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
class MapbenderPrintQueueCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setDescription('Manage print queue.')
            ->setHelp('Manage print queue.')
            ->setName('mapbender:print:queue')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'queue id for which rendering should be started')
            ->addOption('clean', null, InputOption::VALUE_OPTIONAL, 'clean old entries and files they are older then '.$this->getContainer()->getParameter(MapbenderPrintExtension::KEY_MAX_AGE).' days');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var PrintQueueManager $manager */
        $manager = $this->getContainer()->get('mapbender.print.queue_manager');
        $id      = $input->getOption('id');
        $result  = null;

        if ($id) {
            $queue = $manager->getRepository()->findOneBy(intval($id));
            if ($queue) {
                $result = $manager->render($queue);
            } else {
                $result = PrintQueueManager::STATUS_QUEUE_NOT_EXISTS;
            }
        } else {
            $result = $manager->renderNext();
        }

        switch($result){
            case PrintQueueManager::STATUS_QUEUE_NOT_EXISTS:
                $output->writeln('Queue with the given id doesn`t exists.');
                break;

            case PrintQueueManager::STATUS_WRONG_QUEUED:
                $output->writeln('The queue status as if already in the rendering.
                Maybe "started" timestamp  saved wrong.
                Check database entries or try again later.');
                break;

            case PrintQueueManager::STATUS_IN_PROCESS:
                $output->writeln('The queue is already in process. Try again later.');
                break;

            case PrintQueueManager::STATUS_QUEUE_EMPTY:
                $output->writeln('The queue is empty. Try again later.');
                break;

            default:
                /** @var PrintQueue $entity */
                $entity = $result;
                $output->writeln('New PDF is rendered successfully:\n'. $manager->getPdFilePath($entity));
        }
    }
} 