<?php

namespace Mapbender\CoreBundle\Command;

use Symfony\Component\Console\Style\SymfonyStyle;

interface ConfigCheckExtension
{
    public function getName(): string;
    public function execute(SymfonyStyle $output): bool;
}
