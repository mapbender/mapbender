<?php

namespace Mapbender\WmsBundle\Command;

use DBSIMM\ImmoBundle\Element\TileControl;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\WmsInstanceEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ServiceUrlRewriteHostCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('mapbender:services:rewrite:host')
            ->setDescription('Replace host name in configured WMS / WFS services.')
            ->addArgument('from', InputArgument::REQUIRED, 'Old host name to scan for.')
            ->addArgument('to', InputArgument::REQUIRED, 'New host name to set where old name was used.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run through logic but skip database writeback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $this->updateWmsLayerSources($input, $output, $dryRun);
        $this->updateWmsSources($input, $output, $dryRun);
        $this->updateWmsInstances($input, $output, $dryRun);
        $this->updateWfsUrls($input, $output, $dryRun);
        $this->updateTileControl($input, $output, $dryRun);
        if (!$dryRun) {
            $doctrine = $this->getContainer()->get('doctrine');
            $doctrine->getManager()->flush();
        }
    }

    protected function updateWmsSources(InputInterface $input, OutputInterface $output, $dryRun = false)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();


        $sources = $this->getRepository('MapbenderWmsBundle:WmsSource')->findAll();

        $nSources = count($sources);
        $output->writeln("<info>Updating WMS sources</info> ($nSources)");
        $progress = $this->createProgressBar($output, $nSources);

        foreach ($sources as $source) {
            /** @var WmsSource $source */
            $em->persist($source);
            $em->persist($source->getContact());
            $eh = WmsSourceEntityHandler::createHandler($this->getContainer(), $source);
            /** @var WmsSource $source */
            $source->replaceHost($to, $from);

            if (!$dryRun) {
                $eh->update($source);
                $em->persist($source->getContact());
                $em->flush();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    protected function updateWmsLayerSources(InputInterface $input, OutputInterface $output, $dryRun = false)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        // Get ALL layer source entities
        $instanceRepository = $doctrine->getRepository('MapbenderWmsBundle:WmsLayerSource');
        $layourSources = $instanceRepository->findBy(array(), array('id' => 'ASC'));
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $doctrine->getConnection();

        $nLayerSources = count($layourSources);
        $output->writeln("<info>Updating WMS layer sources</info> ($nLayerSources)");
        $progress = $this->createProgressBar($output, $nLayerSources);

        foreach ($layourSources as $layerSource) {
            /** @var WmsLayerSource $layerSource */
            $layerSource->replaceHost($to, $from);

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

    /**
     * @param $entityName
     * @return EntityRepository
     */
    protected function getRepository($entityName)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        return $doctrine->getRepository($entityName);
    }

    protected function updateWmsInstances(InputInterface $input, OutputInterface $output, $dryRun = false)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');

        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        // Get ALL WMS instances from db
        $instanceRepository = $this->getRepository('MapbenderWmsBundle:WmsInstance');
        $iqb = $instanceRepository->createQueryBuilder('i');
        $instances = $iqb
            ->orderBy('i.title')
            ->getQuery()
            ->getResult();

        $nInstances = count($instances);
        $output->writeln("<info>Updating WMS instances</info> ($nInstances)");
        $progress = $this->createProgressBar($output, $nInstances);

        foreach ($instances as &$instance) {
            $fqt = $instance->getTitle();
            if (!$instance instanceof WmsInstance) {
                $output->writeln('<error>Skipping "' . $fqt . '" – not a WMS instance.</error>');
                continue;
            }

            $instance->getSource()->replaceHost($to, $from);
            $instance->setSource($instance->getSource());

            $handler = WmsInstanceEntityHandler::createHandler($this->getContainer(), $instance);

            if (!$dryRun) {
                $handler->save();
                $em->flush();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    /**
     * @param string $className
     * @return Element[]
     */
    protected function getElementsByClassName($className)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $elementRepository */
        $elementRepository = $doctrine->getRepository('MapbenderCoreBundle:Element');

        $eqb = $elementRepository->createQueryBuilder('e');
        $elements = $eqb->where('e.class = :class')
            ->setParameter('class', $className)
            ->getQuery()
            ->getResult();
        return $elements;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getEntityManager()
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        return $doctrine->getManager();
    }

    protected function updateTileControl(InputInterface $input, OutputInterface $output, $dryRun = false)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');
        $elements = $this->getElementsByClassName('DBSIMM\ImmoBundle\Element\TileControl');
        $nElements = count($elements);
        if ($nElements) {
            $title = TileControl::getClassTitle();
            $output->writeln("<info>Updating {$nElements} {$title} elements</info> ()");
            $progress = $this->createProgressBar($output, $nElements);
        } else {
            return;
        }

        $em = $this->getEntityManager();

        foreach ($elements as $e) {
            $config = $e->getConfiguration();

            $targetUrl = ArrayUtil::getDefault($config, 'substituteUrl', null);
            if (!$targetUrl) {
                continue;
            }
            $newTargetUrl = str_replace($from, $to, $targetUrl);
            if ($newTargetUrl !== $targetUrl) {
                if (!$dryRun) {
                    $em->persist($e);
                    $config['substituteUrl'] = $newTargetUrl;
                    $e->setConfiguration($config);
                    $em->flush();
                }
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    protected function updateWfsUrls(InputInterface $input, OutputInterface $output, $dryRun = false)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');
        $em = $this->getEntityManager();

        $elements = $this->getElementsByClassName('DBSIMM\ImmoBundle\Element\WFS');
        $nElements = count($elements);
        $output->writeln("<info>Updating WFS elements</info> ($nElements)");
        $progress = $this->createProgressBar($output, $nElements);

        foreach ($elements as $element) {
            $em->persist($element);
            $conf = $element->getConfiguration();
            foreach ($conf['layers'] as &$layer) {
                $layer['url'] = UrlUtil::replaceHost($layer['url'], $to, $from);
            }
            $element->setConfiguration($conf);
            if (!$dryRun) {
                $em->flush();
            }
            $progress->advance(1);
        }
        $progress->finish();
    }

    /**
     * Create progress bar helper
     *
     * @param  OutputInterface $output
     * @param  integer $maxCount
     * @return ProgressHelper
     */
    protected function createProgressBar(OutputInterface $output, $maxCount)
    {
        $progressBar = clone $this->getHelper('progress');
        $progressBar->setFormat(' %current%/%max% [<info>%bar%</info>] %percent%% Elapsed: %elapsed%');
        $progressBar->setBarCharacter('∎');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setBarWidth(20);
        $progressBar->setProgressCharacter("·");
        $progressBar->setRedrawFrequency(1);
        $progressBar->start($output, $maxCount);
        return $progressBar;
    }
}
