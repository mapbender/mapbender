<?php

namespace Mapbender\VectorTilesBundle;

use Mapbender\CoreBundle\Command\ConfigCheckExtension;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class PrintConfigCheckCommand implements ConfigCheckExtension
{
    protected array $rows = [];

    public function getName(): string
    {
        return "Vector Tiles Print Configuration";
    }

    public function execute(SymfonyStyle $output): bool
    {
        $headers = ['Component', 'Status', 'Version'];
        $this->rows = [];

        $components = [
            "node" => "Node.js",
            "puppeteer" => "Puppeteer",
        ];

        // TODO: this works on UNIX-like systems only, not on Windows. Adapt and test this on windows.
        foreach ($components as $processName => $processTitle) {
            if (!$this->checkForProcess($processName, $processTitle)) {
                $output->table($headers, $this->rows);
                return false;
            }
        }

        $browsers = $this->getPuppeteerBrowsers();
        $message = count($browsers) > 0 ? '<fg=green>' . count($browsers) . ' installed</>' : '<fg=red>no browsers installed</>';
        $this->rows[] = ['puppeteer Browsers', $message, implode("\n", $browsers)];
        $output->table($headers, $this->rows);
        return count($browsers) > 0;
    }

    protected function checkForProcess(string $processName, string $processTitle): bool
    {
        $processWhich = new Process(['which', $processName]);
        $processWhich->run();
        if (!$processWhich->getOutput()) {
            $this->rows[] = [$processTitle, '<fg=red>not found</>', '–'];
            return false;
        }

        $processVersion = new Process([$processName, '--version']);
        $processVersion->run();
        $this->rows[] = [$processTitle, '<fg=green>found</>', trim($processVersion->getOutput())];
        return true;
    }

    protected function getPuppeteerBrowsers(): array
    {
        $process = new Process(['puppeteer', 'browsers', 'list']);
        $process->run();
        $browserString = trim($process->getOutput());

        if (!$browserString) {
            return [];
        }

        $browsers = explode("\n", $browserString);

        // strip away path to browser executables (too much information)
        return array_map(function ($browser) {
            return implode(" ", explode(" ", $browser, -1));
        }, $browsers);
    }
}
