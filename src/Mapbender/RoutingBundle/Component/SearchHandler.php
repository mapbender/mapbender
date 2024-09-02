<?php

namespace Mapbender\RoutingBundle\Component;

use Exception;
use Mapbender\RoutingBundle\Component\RequestHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SearchHandler extends RequestHandler {


    protected function createQuery($configuration,$terms) {
        $query = "";
        $q =  trim(urldecode($terms));
        // Delete to many WhiteSpaces
        $q = preg_replace('/\s\s+/', ' ', $q);
        // Check isset Params of 'token_regex_in' and 'token_regex_out' and 'token_regex' then search and replace with this Params
        // If False then take 'token_regex' or default Search/Replace  params for query-String
        if ( isset($configuration['token_regex_in']) && isset($configuration['token_regex_out']) && isset($configuration['token_regex'])){
            # token_regex Tokenizer split regexp.            'token_regex'     => '[^a-zA-Z0-9äöüÄÖÜß]',
            # token_regex_in Tokenizer search regexp.        'token_regex_in'  => '([a-zA-ZäöüÄÖÜß]{3,})',
            # token_regex_out Tokenizer replace regexp.      'token_regex_out' => '$1*',
            // check SplitPattern
            if (isset($configuration['token_regex'])) {
                $splitPattern = $configuration['token_regex'];
                $q = preg_replace($splitPattern, " ", $q);
            }

            $tokens = explode(" ", $q);
            $searchPattern = $configuration['token_regex_in'];
            $replacePattern = $configuration['token_regex_out'];

            foreach($tokens as $term) {
                if ($term == "") {
                    continue;
                }

                $query .= preg_replace($searchPattern, $replacePattern, $term);
            }

            $query = "*" . trim($query,$replacePattern) . "*";
        }
        else{

            // check if isset 'token_regex'/ Replace-Pattern
            // not set then take Default[^a-zA-Z0-9äöüÄÖÜß] as Pattern
            if (isset($configuration['token_regex'])) {
                $pattern = $configuration['token_regex'];
                $q = preg_replace($pattern, " ", $q);
            }
            else{
                $q = preg_replace("/[^a-zA-Z0-9äöüÄÖÜß]/", " ", $q);
            }

            // Replace Whitespace if desired
            // Example= 'Anne Frank' => 'Anne+Frank'
            if (array_key_exists('query_ws_replace', $configuration)) {
                $pattern = $configuration['query_ws_replace'];
                if ('' !== trim($pattern)) {
                    $q = preg_replace('/\s+/', $pattern, $q);
                }
            }

            // Split with " "-Delimter to 2 Words für SQLR-Query
            // Example= 'Anne Frank' => '*Anne*+*Frank*'
            foreach(explode(" ", $q) as $term) {
                if ($term == "") {
                    continue;
                }
                $query .= " *" . $term . "*";
            }
        }

        return trim($query);
    }

    /**
     * @param array $configuration
     * @param ContainerInterface $container
     * @return Response
     * @throws Exception
     */

    public function getAction($configuration,$container)
    {

        $configuration = $this->getSearchConfiguration($configuration);

        $request       = $container->get('request');

        $qf            = $configuration['query_format'] ?? '%s';
        $query         =  $this->createQuery($configuration,$request->get('terms', ''));

        // Encode-Query
        $encoded_query = urlencode($query);

        // Build query URL
        $url = $configuration['query_url'];
        $url .= (false === strpos($url, '?') ? '?' : '&');
        $url .= $configuration['query_key'] . '=' . sprintf($qf, $encoded_query);

        // Response via php-curl
        // temp-Search-Wrapper
        // get Response
        $curlResponse = self::getCurlResponse($url);

        // Search-Curl-Response Error-Handling
        if ($curlResponse['responseCode'] != 200 ){

            $inputCode = $curlResponse['responseCode'];
            $responseArrayMessage= 'SearchDriver is not valid';
            $responseArrayMessageDetails = $curlResponse['curl_error'];

            // create Error-Array
            $errorArray = array(
                'error' =>array(
                    'code' => $inputCode,
                    'apiMessage' => $responseArrayMessage,
                    'messageDetails' => $responseArrayMessageDetails,
                )
            );

            // Set Response and fill with Responsedata
            $response = new JsonResponse($errorArray['error'], 500, array('Content-Type', 'application/json'));


        }else {

            // decompose curlResponse
            $response = $curlResponse['responseData'];

            // Dive into result JSON if needed (Solr for example 'response.docs')
            if (!empty($configuration['collection_path'])) {
                $data = json_decode($response, true);
                foreach (explode('.', $configuration['collection_path']) as $key) {
                    $data = $data[$key];
                }

                // Set Response and fill with Responsedata
                $response = new JsonResponse($data, 200, array('Content-Type', 'application/json'));

            }
        }

        return $response;
    }


    /**
     * Get Configuration from BackenedAdmintype, validation and create HttpActionTemplate
     * @param $defaultConfiguration
     * @return array
     */
    public function getSearchConfiguration($defaultConfiguration)
    {
        $configuration= array();

        # Search the admintype of the item for the Search-Config
        # If the search config of the admin type has ever been defined, then it takes the settings
        if ( count($defaultConfiguration['search']) != 0 && $defaultConfiguration['addSearch'] = true){

            $configuration = $defaultConfiguration['search'];

            switch ($configuration['searchDriver']){
                case 'solr':

                    $solr = array(
                        #'search_Titel'    => $configuration['searchTitel'],
                        'query_url'       => $configuration['searchUrl'],
                        'query_key'       => $configuration['query_key'] ?? 'q',
                        'query_ws_replace'=> $configuration['query_ws_replace'] ?? '',
                        'query_format'    => $configuration['query_format'] ?? '%s',
                        'token_regex'     => $configuration['token_regex'] ?? null,
                        'token_regex_in'  => $configuration['token_regex_in'] ?? null,
                        'token_regex_out' => $configuration['token_regex_out'] ?? null,
                        'collection_path' => $configuration['collection_path'] ?? 'response.docs',
                        'label_attribute' => $configuration['label_attribute'] ?? 'label',
                        'geom_attribute'  => $configuration['geom_attribute'] ?? 'geom',
                        'geom_format'     => $configuration['geom_format'] ?? 'WKT'
                    );

                    $configuration = $solr;
                    break;
            }
        }

        return $configuration;
    }


    /**
     * @param $graphQueryUrl
     * @return array
     */
    private static function getCurlResponse($graphQueryUrl){
        # curl-setting
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => $graphQueryUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER, array('Content-type: application/json') // Assuming you're requesting JSON
        ));

        $response = curl_exec($curl);
        #$info = curl_getinfo($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        # Create Curl-Response
        $curlResponse = array(
            'responseData' => $response,
            'responseCode' => $code,
            'curl_error' => $err
        );

        return $curlResponse;
    }

}
