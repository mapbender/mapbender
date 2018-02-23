<?php

namespace Mapbender\WmsBundle\Command;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\Transformer\CliHelperWrapper;
use Mapbender\CoreBundle\Component\Transformer\UrlHostRewriter;
use Mapbender\CoreBundle\Component\Transformer\ValueTransformerBase;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class HostRewriteCommand extends ContainerAwareCommand
{
    protected $progressOutput;

    protected function configure()
    {
        $this
            ->setName('mapbender:wms:rewrite:host')
            ->setDescription('Replace host name in configured WMS services.')
            ->addArgument('from', InputArgument::REQUIRED, 'Old host name to scan for.')
            ->addArgument('to', InputArgument::REQUIRED, 'New host name to set where old name was used.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run through logic but skip database writeback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');

        if ($dryRun || $output->getVerbosity() >= 2) {
            $detailOutput = $output;
            $this->progressOutput = new NullOutput();
        } else {
            $detailOutput = new NullOutput();
            $this->progressOutput = $output;
        }


        $rewriter = $this->getRewriter($input, $detailOutput);
        $rewriterWrapper = new CliHelperWrapper($rewriter, function ($before, $after) use ($output) {
            $output->writeln(" *   " . var_export($before, true) . "\n  \\=>" . var_export($after, true));
        });

        $this->updateWmsLayerSources($output, $rewriterWrapper, $dryRun);
        $this->updateWmsSources($output, $rewriterWrapper, $dryRun);
        $this->updateWmsInstances($output, $rewriterWrapper, $dryRun);

        if (!$dryRun) {
            $doctrine = $this->getContainer()->get('doctrine');
            $doctrine->getManager()->flush();
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ValueTransformerBase
     */
    protected function getRewriter(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        $rewriter = new UrlHostRewriter($to, $from, true);
        return $rewriter;
    }

    protected function updateWmsSources(OutputInterface $output, ValueTransformerBase $rewriter, $dryRun = false)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        // Get ALL sources
        $instanceRepository = $doctrine->getRepository('MapbenderWmsBundle:WmsSource');
        $sources = $instanceRepository->findAll();


        $nSources = count($sources);
        $output->writeln("<info>Updating WMS sources</info> ($nSources)");
        $progress = $this->createProgressBar($nSources);

        foreach ($sources as $source) {
            /** @var WmsSource $source */
            $em->persist($source);
            $contact = $source->getContact();
            if ($contact) {
                $em->persist($contact);
            }
            $eh = EntityHandler::createHandler($this->getContainer(), $source);
            /** @var WmsSourceEntityHandler $eh */
            $source->rewriteUrl($rewriter);

            if (!$dryRun) {
                $eh->update($source);
                $em->persist($source->getContact());
                $em->flush();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    protected function updateWmsLayerSources(OutputInterface $output, ValueTransformerBase $rewriter, $dryRun = false)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        // Get ALL layer source entities
        $instanceRepository = $doctrine->getRepository('MapbenderWmsBundle:WmsLayerSource');
        $layourSources = $instanceRepository->findBy(array(), array('id' => 'ASC'));
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $doctrine->getConnection();

        $nLayerSources = count($layourSources);
        $output->writeln("<info>Updating WMS layer sources</info> ($nLayerSources)");
        $progress = $this->createProgressBar($nLayerSources);

        foreach ($layourSources as $layerSource) {
            /** @var WmsLayerSource $layerSource */
            $layerSource->rewriteUrl($rewriter);

            // the entity handler for layer sources doesn't work. Nothing will be written to db.
            // We have to do the updates directly
            $sql = "UPDATE mb_wms_wmslayersource SET styles = :styles WHERE id = :id";
            $queryParams = array(
                ':styles' => serialize($layerSource->getStyles()),
                ':id'     => $layerSource->getId(),
            );
            $stmt = $connection->prepare("$sql");
            foreach ($queryParams as $paramName => $paramValue) {
                $stmt->bindValue($paramName, $paramValue);
            }

            if (!$dryRun) {
                $stmt->execute();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    protected function updateWmsInstances(OutputInterface $output, ValueTransformerBase $rewriter, $dryRun = false)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        // Get ALL WMS instances from db
        $instanceRepository = $doctrine->getRepository('MapbenderWmsBundle:WmsInstance');
        $iqb = $instanceRepository->createQueryBuilder('i');
        $instances = $iqb
            ->orderBy('i.title')
            ->getQuery()
            ->getResult();

        $nInstances = count($instances);
        $output->writeln("<info>Updating WMS instances</info> ($nInstances)");
        $progress = $this->createProgressBar($nInstances);

        foreach ($instances as &$instance) {
            $fqt = $instance->getTitle();
            if (!$instance instanceof WmsInstance) {
                $output->writeln('<error>Skipping "' . $fqt . '" – not a WMS instance.</error>');
                continue;
            }

            $instance->getSource()->rewriteUrl($rewriter);
            $instance->setSource($instance->getSource());

            $handler = EntityHandler::createHandler($this->getContainer(), $instance);
            $handler->generateConfiguration();

            if (!$dryRun) {
                $handler->save();
                $em->flush();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    /**
     * Create progress bar helper
     *
     * @param  integer $maxCount
     * @return ProgressHelper
     */
    protected function createProgressBar($maxCount)
    {
        $progressBar = clone $this->getHelper('progress');
        $progressBar->setFormat(' %current%/%max% [<info>%bar%</info>] %percent%% Elapsed: %elapsed%');
        $progressBar->setBarCharacter('∎');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setBarWidth(20);
        $progressBar->setProgressCharacter("·");
        $progressBar->setRedrawFrequency(1);
        $progressBar->start($this->progressOutput, $maxCount);
        return $progressBar;
    }
}
