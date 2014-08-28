<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Simple Search - Just type, select and show result
 *
 * @author Christian Wygoda
 */
class SimpleSearch extends Element
{
    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.core.simplesearch.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.simplesearch.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array(
            "mb.core.search.tag.search");
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/autocomplete.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.simplesearch.js',
                ),
            'css' => array(),
            'trans' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SimpleSearchAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:simplesearch.html.twig';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'query_url' => 'http://',
            'query_key' => 'q',
            'query_format' => '%s',
            'token_regex_in' => '([a-zA-Z]{3,})',
            'token_regex_out' => '$1*',
            'collection_path' => '',
            'label_attribute' => 'label',
            'geom_attribute' => 'geom',
            'geom_format' => 'WKT',
            'delay' => 300);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSimpleSearch';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:simplesearch.html.twig',
                    array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action) {
        $configuration = $this->getConfiguration();

        $q = $this->container->get('request')->get('term', '');
        $qf = $configuration['query_format'] ? $configuration['query_format'] : '%s';

        // Replace Whitespace if desired
        if(array_key_exists('query_ws_replace', $configuration)) {
            $pattern = $configuration['query_ws_replace'];
            if('' !== trim($pattern)) {
                $q = preg_replace( '/\s+/', $pattern, $q);
            }
        }

        // Build query URL
        $url = $configuration['query_url'];
        $url .= (false === strpos($url, '?') ? '?' : '&');
        $url .= $configuration['query_key'] . '=' . sprintf($qf, $q);

        $path = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:genericProxy',
            'url' => $url
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        $response = $this->container->get('http_kernel')->handle(
                $subRequest, HttpKernelInterface::SUB_REQUEST);
        // Dive into result JSON if needed (Solr for example 'response.docs')
        if('' !== $configuration['collection_path']) {
            $data = json_decode($response->getContent(), true);
            foreach(explode('.', $configuration['collection_path']) as $key) {
                $data = $data[$key];
            }
            $response->setContent(json_encode($data));
        }

        // In dev environment, add query URL as response header for easier debugging
        if($this->container->get('kernel')->isDebug()) {
            $response->headers->set('X-Mapbender-SimpleSearch-URL', $url);
        }

        return $response;
    }
}
