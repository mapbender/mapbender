<?php


namespace Mapbender\IntrospectionBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslationInspectCommand extends AbstractTranslationCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $catalogs = $this->getCatalogs($this->allCatalogNames);
        $referenceCatalogs = array(
            $catalogs['en'],
            $catalogs['de'],
        );
        $this->showIdentityTranslations($output, $catalogs);
        $this->showRepetitions($output, $referenceCatalogs, $catalogs);
    }

    /**
     * @param string[] $catalogNames
     * @return MessageCatalogueInterface[] keyed on catalog name
     */
    protected function getCatalogs($catalogNames)
    {
        $catalogsOut = array();
        foreach ($catalogNames as $name) {
            $catalogsOut[$name] = $this->getCatalog($name);
        }
        return $catalogsOut;
    }

    /**
     * @param OutputInterface $output
     * @param MessageCatalogueInterface[] $catalogs
     */
    protected function showIdentityTranslations($output, $catalogs)
    {
        foreach ($catalogs as $catalog) {
            foreach ($catalog->getDomains() as $domain) {
                $idents = array();
                foreach ($catalog->all($domain) as $key => $message) {
                    if ($key == trim($message)) {
                        $idents[] = $key;
                    }
                }
                if ($idents) {
                    $identCount = count($idents);
                    $output->writeln("Catalog {$catalog->getLocale()} contains {$identCount} identity translation(s) in domain {$domain}:");
                    foreach ($idents as $messageKey) {
                        $output->writeln(" {$messageKey}");
                    }
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param MessageCatalogueInterface[] $referenceCatalogs
     * @param MessageCatalogueInterface[] $allCatalogs
     * @param string $domain
     */
    protected function showRepetitions(OutputInterface $output, $referenceCatalogs, $allCatalogs, $domain = 'messages')
    {
        foreach ($referenceCatalogs as $referenceIndex => $referenceCatalog) {
            $referenceMessages = $referenceCatalog->all($domain);
            foreach ($allCatalogs as $scanCatalog) {
                $headerPrinted = false;
                if ($scanCatalog === $referenceCatalog) {
                    continue;
                }
                $scanMessages = $scanCatalog->all($domain);
                $common = array_intersect_key($referenceMessages, $scanMessages);
                foreach ($common as $key => $referenceTranslation) {
                    $scanTranslation = $scanMessages[$key];
                    if ($scanTranslation === $referenceTranslation) {
                        if (!$headerPrinted) {
                            $output->writeln("Catalog {$scanCatalog->getLocale()} repeats messages from {$referenceCatalog->getLocale()}:");
                            $headerPrinted = true;
                        }
                        $output->writeln("   {$key} : " . print_r($referenceTranslation, true));
                    }
                }
            }
        }
    }
}
