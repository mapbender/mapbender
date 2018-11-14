<?php

namespace Mapbender\CoreBundle\Command;

use Mapbender\CoreBundle\Asset\ApplicationAssetCache;
use Mapbender\CoreBundle\Mapbender;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Christian Wygoda
 * @deprecated should be removed in release/3.0.7
 */
class DumpMapbenderAssetsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Dump all Mapbender application assets.')
            ->setHelp(<<<EOT
The <info>mapbender:assets:dump</info> dumps all Mapbender application assets.
EOT
            )
            ->setName('mapbender:assets:dump');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Mapbender $mapbender */
        $mapbender = $this->getContainer()->get('mapbender');
        $extraProviders = array(
            'Mapbender\CoreBundle\Component\Application',
            'Mapbender\WmsBundle\Entity\WmsInstance',
        );
        $assetProviders = array_merge(
            $mapbender->getElements(),
            $mapbender->getTemplates(),
            $mapbender->getLayers(),
            $extraProviders);

        $assets = array();
        foreach($assetProviders as $provider) {
            $providerAssets = array();
            foreach($provider::listAssets() as $type => $files) {
                if($type === 'trans') {
                    continue;
                }

                $providerAssets[$type] = array();
                foreach($files as $file) {
                    $reference = $this->getReference($provider, $file);
                    $providerAssets[$type][] = $reference;
                }
            }
            $assets = array_merge_recursive($assets, $providerAssets);
        }
        $assets = array_map(function(&$items) {
            return array_unique($items);
        }, $assets);

        foreach($assets as $type => $items) {
            $output->writeln(sprintf('Considering %d assets of type %s...', count($items), $type));
            $cache = new ApplicationAssetCache($this->getContainer(), $items, $type, true);
            $assets = $cache->fill();
        }
    }

    /**
     * Build an Assetic reference path from a given objects bundle name(space)
     * and the filename/path within that bundles Resources/public folder.
     *
     * @todo     : This is duplicated from Component\Application
     *
     * @param        $class
     * @param string $file
     * @return string
     * @internal param object $object
     */
    private function getReference($class, $file)
    {
        // If it starts with an @ we assume it's already an assetic reference
        if ($file[0] !== '@') {
            $namespaces = explode('\\', $class);
            $bundle = sprintf('%s%s', $namespaces[0], $namespaces[1]);
            return sprintf('@%s/Resources/public/%s', $bundle, $file);
        } else {
            return $file;
        }
    }
}

