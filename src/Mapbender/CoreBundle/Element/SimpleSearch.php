<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Simple Search - Just type, select and show result
 *
 * @author Christian Wygoda
 */
class SimpleSearch extends Element
{
    public static function getClassTitle()
    {
        return 'mb.core.simplesearch.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.simplesearch.class.description';
    }

    public static function getClassTags()
    {
        return array(
            'mb.core.search.tag.search',
        );
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
                "@MapbenderManagerBundle/Resources/public/sass/element/simple_search.scss"
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:simple_search.json.twig'
            )
        );
    }


    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $configuration = $this->getConfiguration();
        $request       = $this->container->get('request_stack')->getCurrentRequest();
        $q             = $request->get('term', '');
        $qf            = $configuration['query_format'] ? $configuration['query_format'] : '%s';
        $kernel        = $this->container->get('kernel');

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
        $path       = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:genericProxy',
            'url'         => $url
        );
        $subRequest = $request->duplicate(array(), null, $path);
        $httpKernel = $this->container->get('http_kernel');
        $response   = $httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        // Dive into result JSON if needed (Solr for example 'response.docs')
        if (!empty($configuration['collection_path'])) {
            $data = json_decode($response->getContent(), true);
            foreach (explode('.', $configuration['collection_path']) as $key) {
                $data = $data[ $key ];
            }
            $response->setContent(json_encode($data));
        }

        // In dev environment, add query URL as response header for easier debugging
        if ($kernel->isDebug()) {
            $response->headers->set('X-Mapbender-SimpleSearch-URL', $url);
        }

        return $response;
    }
}
