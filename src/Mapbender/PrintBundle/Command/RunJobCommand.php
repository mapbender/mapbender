<?php


namespace Mapbender\PrintBundle\Command;

use Mapbender\PrintBundle\Component\PrintService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;


/**
 * Class JobCommand
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Rolf Neuberger <rolf.neuberger@wheregroup.com>
 * @copyright 2017 by WhereGroup GmbH & Co. KG
 */
class RunJobCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription("Run a print job from a dumped job definition json or yaml")
            ->setName('mapbender:print:runJob')
            ->addArgument('inputFile', InputArgument::REQUIRED, 'JSON or YAML job file to load ("-" for stdin)')
            ->addArgument('outputFile', InputArgument::REQUIRED, 'Output PDF file path')
        ;
    }

    /**
     * @return PrintService
     */
    protected function getPrintService()
    {
        $container = $this->getContainer();
        $printServiceClassName = $container->getParameter('mapbender.print.service.class');
        return new $printServiceClassName($container);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobArray = $this->getJobArray($input->getArgument('inputFile'));

        // PrintService::doPrint returns the PDF body as a binary string
        $service = $this->getPrintService();
        $outputBody = $service->doPrint($jobArray);

        $outputFileName= $input->getArgument('outputFile');
        file_put_contents($outputFileName, $outputBody);
        $outputSize = strlen($outputBody);
        $output->writeln("$outputSize bytes written to $outputFileName");
    }

    /**
     * Check if an option value is part of a limited choice set, and return it if it is. Otherwise throws.
     * If $caseInsensitive (default), return value will be taken from the given $validValues as is.
     *
     * @param InputInterface $input
     * @param string $optionName
     * @param string[] $allowedChoices
     * @param bool $caseInsensitive
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function getChoiceOption(InputInterface $input, $optionName, $allowedChoices, $caseInsensitive=true)
    {
        $requestedMode = $input->getOption($optionName);
        foreach ($allowedChoices as $validChoice) {
            if ($caseInsensitive) {
                $match = mb_strtolower($requestedMode) == mb_strtolower($validChoice);
            } else {
                $match = $requestedMode == $validChoice;
            }
            if ($match) {
                return $validChoice;
            }
        }
        throw new \InvalidArgumentException("Unsupported $optionName " . var_export($requestedMode, true));
    }

    /**
     * @param $fileName
     * @return mixed
     * @throws ParseException
     */
    protected function getJobArray($fileName)
    {
        if ($fileName == '-') {
            $body = file_get_contents('php://stdin');
        } else {
            $body = file_get_contents($fileName);
        }
        try {
            $decoded = Yaml::parse($body);
        } catch (ParseException $e) {
            $decoded = json_decode($body, true);
            if ($decoded === NULL) {
                throw $e;
            }
        }
        return $decoded;
    }
}
