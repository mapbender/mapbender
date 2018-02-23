<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\CoreBundle\Component\Transformer\RegexRewriter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rewrites WMS urls based on regex search + replace
 * $ ../app/console mapbender:wms:rewrite:regex '^http://ows.t[er]{1,4}stris.de' 'https://wow.yeah.com' --dry-run
    Updating WMS layer sources (538)
     *   'http://ows.terrestris.de/osm/service?styles=&layer=OSM-WMS&service=WMS&format=image%2Fpng&sld_version=1.1.0&request=GetLegendGraphic&version=1.1.1'
      \=>'https://wow.yeah.com/osm/service?styles=&layer=OSM-WMS&service=WMS&format=image%2Fpng&sld_version=1.1.0&request=GetLegendGraphic&version=1.1.1'
    Updating WMS sources (45)
     *   'http://ows.terrestris.de/osm/service?request=GetCapabilities&service=wms'
      \=>'https://wow.yeah.com/osm/service?request=GetCapabilities&service=wms'
     *   'http://ows.terrestris.de/osm/service'
      \=>'https://wow.yeah.com/osm/service'
     *   'http://ows.terrestris.de/osm/service'
      \=>'https://wow.yeah.com/osm/service'
     *   'http://ows.terrestris.de/osm/service'
      \=>'https://wow.yeah.com/osm/service'
     *   'http://ows.terrestris.de/osm/service'
      \=>'https://wow.yeah.com/osm/service'

 */
class RegexRewriteCommand extends HostRewriteCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('mapbender:wms:rewrite:regex');
        $definition = $this->getDefinition();
        $arguments = $definition->getArguments();
        $arguments['from'] = new InputArgument('from', InputArgument::REQUIRED, 'Search pattern (regular expression)');
        $arguments['to'] = new InputArgument('to', InputArgument::REQUIRED, 'Replacement');
    }

    protected function getRewriter(InputInterface $input, OutputInterface $output)
    {
        $nativePattern = $input->getArgument('from');
        $phpPattern = $this->nativeRegexToPhpRegex($nativePattern, 'i');
        $replacement = $input->getArgument('to');

        return new RegexRewriter($phpPattern, $replacement, true);
    }

    public static function nativeRegexToPhpRegex($pattern, $flags)
    {
        $potentialDelimiters = array(
            '#',
            '/',
            '|',
        );
        foreach ($potentialDelimiters as $delim) {
            if (strpos($pattern, $delim) === false) {
                return "{$delim}{$pattern}{$delim}{$flags}";
            }
        }
        $delim = '#';
        return $delim . str_replace('#', '\\#', $pattern) . $delim . $flags;

    }
}
