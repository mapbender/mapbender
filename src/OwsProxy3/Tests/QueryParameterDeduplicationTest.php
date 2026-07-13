<?php

namespace OwsProxy3\Tests;

use OwsProxy3\CoreBundle\Component\Utils;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class QueryParameterDeduplicationTest extends TestCase
{
    public function getTestDataQueryParamaterDeduplication(): array
    {
        return [
            [
                // url without params is not modified #1
                'protocol://host/path/host/path',
                'protocol://host/path/host/path',
                'protocol://host/path/host/path',
            ],
            [
                // url without params is not modified #2
                'protocol://host/path/host/path?',
                'protocol://host/path/host/path?',
                'protocol://host/path/host/path?',
            ],
            [
                // url without params is not modified #3
                'protocol://host/path/host/path?#cat=hat&cat=hat',
                'protocol://host/path/host/path?#cat=hat&cat=hat',
                'protocol://host/path/host/path?#cat=hat&cat=hat',
            ],
            [
                // no-value parameter first prevails over valued param later
                '?CaT&CaT=mc&cat&CAT=uc&cat=lc',
                '?CaT&cat&CAT=uc',
                '?CaT',
            ],
            [
                // valued parameter first prevails over no-value param later
                '?CaT=mc&CaT&cat&CAT=uc&cat=lc',
                '?CaT=mc&cat&CAT=uc',
                '?CaT=mc',
            ],
            [
                // dangling separator is maintained: no params
                'path?',
                'path?',
                'path?',
            ],
            [
                // dangling separator is maintained: params, no duplicates
                'path?cat=hat&',
                'path?cat=hat&',
                'path?cat=hat&',
            ],
            [
                // dangling separator is maintained: params, duplicates
                'path?cat=hat1&cat=hat2&CAT=HAT3&',
                'path?cat=hat1&CAT=HAT3&',
                'path?cat=hat1&',
            ],
            [
                // fragment is not processed
                'path?cat=hat&CAT=HAT#cat=fragment-hat&CAT=FRAGMENT-HAT',
                'path?cat=hat&CAT=HAT#cat=fragment-hat&CAT=FRAGMENT-HAT',
                'path?cat=hat#cat=fragment-hat&CAT=FRAGMENT-HAT',
            ],
        ];
    }

    /**
     * @dataProvider getTestDataQueryParamaterDeduplication
     * @param string $url
     * @param string $expectedCaseSensitive
     * @param string $_ unused
     */
    public function testQueryParameterDeduplicationCaseSensitive(string $url, string $expectedCaseSensitive, string $_): void
    {
        $processed = Utils::filterDuplicateQueryParams($url, true);
        $this->assertEquals($expectedCaseSensitive, $processed);
    }

    /**
     * @dataProvider getTestDataQueryParamaterDeduplication
     * @param string $url
     * @param string $_ unused
     * @param string $expectedCaseInsensitive
     */
    public function testQueryParameterDeduplicationCaseInsensitive(string $url, string $_, string $expectedCaseInsensitive): void
    {
        $processed = Utils::filterDuplicateQueryParams($url, false);
        $this->assertEquals($expectedCaseInsensitive, $processed);
    }
}
