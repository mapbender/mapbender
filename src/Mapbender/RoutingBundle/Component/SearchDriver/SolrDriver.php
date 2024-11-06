<?php

namespace Mapbender\RoutingBundle\Component\SearchDriver;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SolrDriver {

    protected HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient) {
        $this->httpClient = $httpClient;
    }

    public function search($requestParams, $searchConfig)
    {
        $queryFormat = $searchConfig['query_format'] ?? '%s';
        $query = $this->createSolrQuery($searchConfig, $requestParams['terms']);
        $encodedQuery = urlencode($query);
        $url = $searchConfig['url'];
        $url .= (!str_contains($url, '?') ? '?' : '&');
        $url .= $searchConfig['query_key'] . '=' . sprintf($queryFormat, $encodedQuery);
        $searchEngineResponse = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        $statusCode = $searchEngineResponse->getStatusCode();

        if ($statusCode != 200 ) {
            $response = [
                'error' => [
                    'message' => '',
                ],
            ];
        } else {
            $response = $searchEngineResponse->toArray(false);

            if (!empty($searchConfig['collection_path'])) {
                foreach (explode('.', $searchConfig['collection_path']) as $key) {
                    $response = $response[$key];
                }
            }
        }

        return $response;
    }

    protected function createSolrQuery($configuration, $terms): string
    {
        $query = '';
        $q = trim(urldecode($terms));
        // Delete to many WhiteSpaces
        $q = preg_replace('/\s\s+/', ' ', $q);
        // Check isset Params of 'token_regex_in' and 'token_regex_out' and 'token_regex' then search and replace with this Params
        // If False then take 'token_regex' or default Search/Replace  params for query-String
        if (isset($configuration['token_regex_in']) && isset($configuration['token_regex_out']) && isset($configuration['token_regex'])) {
            # token_regex Tokenizer split regexp.            'token_regex'     => '[^a-zA-Z0-9äöüÄÖÜß]',
            # token_regex_in Tokenizer search regexp.        'token_regex_in'  => '([a-zA-ZäöüÄÖÜß]{3,})',
            # token_regex_out Tokenizer replace regexp.      'token_regex_out' => '$1*',
            // check SplitPattern
            if (isset($configuration['token_regex'])) {
                $splitPattern = $configuration['token_regex'];
                $q = preg_replace($splitPattern, ' ', $q);
            }

            $tokens = explode(" ", $q);
            $searchPattern = $configuration['token_regex_in'];
            $replacePattern = $configuration['token_regex_out'];

            foreach ($tokens as $term) {
                if ($term == '') {
                    continue;
                }

                $query .= preg_replace($searchPattern, $replacePattern, $term);
            }

            $query = '*' . trim($query,$replacePattern) . '*';
        } else {
            // check if isset 'token_regex'/ Replace-Pattern
            // not set then take Default[^a-zA-Z0-9äöüÄÖÜß] as Pattern
            if (isset($configuration['token_regex'])) {
                $pattern = $configuration['token_regex'];
                $q = preg_replace($pattern, ' ', $q);
            } else {
                $q = preg_replace("/[^a-zA-Z0-9äöüÄÖÜß]/", ' ', $q);
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
            foreach(explode(' ', $q) as $term) {
                if ($term == '') {
                    continue;
                }
                $query .= ' *' . $term . '*';
            }
        }

        return trim($query);
    }
}
