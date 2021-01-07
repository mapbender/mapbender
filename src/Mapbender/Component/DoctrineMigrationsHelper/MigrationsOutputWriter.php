<?php

namespace Mapbender\Component\DoctrineMigrationsHelper;

use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrationsOutputWriter
 *
 * @package Mapbender\Component
 */
class MigrationsOutputWriter extends OutputWriter
{
    public function __construct(OutputInterface $output)
    {
        parent::__construct(function ($message) use ($output) {
            return $output->writeln($message);
        });
    }
}