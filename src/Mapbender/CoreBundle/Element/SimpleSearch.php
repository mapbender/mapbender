<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Request;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Simple Search - Just type, select and show result
 *
 * @author Christian Wygoda
 */
class SimpleSearch extends Element implements ConfigMigrationInterface
{
    public static function getClassTitle()
    {
        return 'mb.core.simplesearch.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.simplesearch.class.description';
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SimpleSearchAdminType';
    }

    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:simple_search.html.twig';
    }

    public function getWidgetName()
    {
        return 'mapbender.mbSimpleSearch';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'query_url'       => 'http://',
            'query_key'       => 'q',
            'query_format'    => '%s',
            'token_regex'     => '[^a-zA-Z0-9äöüÄÖÜß]',
            'token_regex_in'  => '([a-zA-ZäöüÄÖÜß]{3,})',
            'token_regex_out' => '$1*',
            'collection_path' => '',
            'label_attribute' => 'label',
            'geom_attribute'  => 'geom',
            'geom_format'     => 'WKT',
            'delay'           => 300,
            // @todo: add form field
            'sourceSrs' => 'EPSG:4326',
            'query_ws_replace' => null,
            'result_buffer' => 300,
            'result_minscale' => 1000,
            'result_maxscale' => null,
            'result_icon_url' => '/bundles/mapbendercore/image/pin_red.png',
            'result_icon_offset' => '-6,-38',
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:simple_search.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js'    => array(
                '@FOMCoreBundle/Resources/public/js/widgets/autocomplete.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.simplesearch.js',
            ),
            'css'   => array(
                "@MapbenderCoreBundle/Resources/public/sass/element/simple_search.scss"
            ),
            'trans' => array(
                'mb.core.simplesearch.error.*',
            ),
        );
    }

    public function handleHttpRequest(Request $request)
    {
        $configuration = $this->getConfiguration();
        $q             = $request->get('term', '');
        $qf            = $configuration['query_format'] ? $configuration['query_format'] : '%s';

        // Replace Whitespace if desired
        if (array_key_exists('query_ws_replace', $configuration)) {
            $pattern = $configuration['query_ws_replace'];
            if ('' !== trim($pattern)) {
                $q = preg_replace('/\s+/', $pattern, $q);
            }
        }

        // Build query URL
        $url = $configuration['query_url'];
        $url .= (false === strpos($url, '?') ? '?' : '&');
        $url .= $configuration['query_key'] . '=' . sprintf($qf, $q);
        /** @var HttpTransportInterface $transport */
        $transport = $this->container->get('mapbender.http_transport.service');
        $response = $transport->getUrl($url);

        // prepare a valid json null encoding before testing for errors (encode clears json_last_error_msg!)
        $validJsonNull = json_encode('null');
        // Dive into result JSON if needed (Solr for example 'response.docs')
        if (!empty($configuration['collection_path'])) {
            $data = json_decode($response->getContent(), true);
            if ($data === null && $response->getContent() !== $validJsonNull) {
                throw new \RuntimeException("Invalid json response " . json_last_error_msg() . " from " . $url);
            }
            foreach (explode('.', $configuration['collection_path']) as $key) {
                $data = $data[ $key ];
            }
            // Rebuild entire response from scratch to discard potentially invalid upstream headers etc
            // see https://github.com/mapbender/mapbender/issues/1303
            $response = new JsonResponse($data);
        }

        // In dev environment, add query URL as response header for easier debugging
        if ($this->container->getParameter('kernel.debug')) {
            $response->headers->set('X-Mapbender-SimpleSearch-URL', $url);
        }

        return $response;
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $config = $entity->getConfiguration();
        if (!empty($config['result']) && \is_array($config['result'])) {
            if (isset($config['result']['icon_url'])) {
                $config['result_icon_url'] = $config['result']['icon_url'];
            }
            if (isset($config['result']['icon_offset'])) {
                $config['result_icon_offset'] = $config['result']['icon_offset'];
            }
            if (isset($config['result']['buffer'])) {
                $config['result_buffer'] = $config['result']['buffer'];
            }
            if (isset($config['result']['minscale'])) {
                $config['result_minscale'] = $config['result']['minscale'];
            }
            if (isset($config['result']['maxscale'])) {
                $config['result_maxscale'] = $config['result']['maxscale'];
            }
        }
        unset($config['result']);

        if (!empty($config['token_regex']) && \is_array($config['token_regex'])) {
            // Legacy example config quirk: documentation has historically suggested using an
            // invalid array type for token_regex. This works incidentally because JavaScript
            // RegExp constructor promotes everything to string.
            // @see https://docs.mapbender.org/3.0.8/en/functions/search/simplesearch.html#yaml-definition
            // Array values do however break the backend form, causing exceptions when editing
            // a Yaml application cloned into the database.
            $config['token_regex'] = implode(',', $config['token_regex']);
        }
        $entity->setConfiguration($config);
    }

}
