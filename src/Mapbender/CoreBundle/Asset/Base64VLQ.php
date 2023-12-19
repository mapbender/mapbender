<?php
/**
 * Based on the implementation in SCSSPHP:
 * https://raw.githubusercontent.com/leafo/scssphp/v0.8.4/src/SourceMap/Base64VLQ.php
 *
 * Based on the Base 64 VLQ implementation in Closure Compiler:
 * https://github.com/google/closure-compiler/blob/master/src/com/google/debugging/sourcemap/Base64VLQ.java
 *
 * Copyright 2011 The Closure Compiler Authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author John Lenz <johnlenz@google.com>
 * @author Anthon Pang <anthon.pang@gmail.com>
 */

namespace Mapbender\CoreBundle\Asset;

class Base64VLQ
{
    // A Base64 VLQ digit can represent 5 bits, so it is base-32.
    const VLQ_BASE_SHIFT = 5;

    // A mask of bits for a VLQ digit (11111), 31 decimal.
    const VLQ_BASE_MASK = 31;

    // The continuation bit is the 6th bit.
    const VLQ_CONTINUATION_BIT = 32;

    private static array $encodingMap = [
         0 => 'A',  1 => 'B',  2 => 'C',  3 => 'D',  4 => 'E',  5 => 'F',  6 => 'G',  7 => 'H',
         8 => 'I',  9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P',
        16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X',
        24 => 'Y', 25 => 'Z', 26 => 'a', 27 => 'b', 28 => 'c', 29 => 'd', 30 => 'e', 31 => 'f',
        32 => 'g', 33 => 'h', 34 => 'i', 35 => 'j', 36 => 'k', 37 => 'l', 38 => 'm', 39 => 'n',
        40 => 'o', 41 => 'p', 42 => 'q', 43 => 'r', 44 => 's', 45 => 't', 46 => 'u', 47 => 'v',
        48 => 'w', 49 => 'x', 50 => 'y', 51 => 'z', 52 => '0', 53 => '1', 54 => '2', 55 => '3',
        56 => '4', 57 => '5', 58 => '6', 59 => '7', 60 => '8', 61 => '9', 62 => '+', 63 => '/',
    ];

    /**
     * Returns the VLQ encoded value.
     *
     * @param integer $value
     *
     * @return string
     */
    public static function encode($value): string
    {
        $encoded = '';
        $vlq = self::toVLQSigned($value);

        do {
            $digit = $vlq & self::VLQ_BASE_MASK;
            $vlq >>= self::VLQ_BASE_SHIFT;

            if ($vlq > 0) {
                $digit |= self::VLQ_CONTINUATION_BIT;
            }

            $encoded .= self::encodeDigit($digit);
        } while ($vlq > 0);

        return $encoded;
    }

    /**
     * Converts from a two-complement value to a value where the sign bit is
     * is placed in the least significant bit.  For example, as decimals:
     *   1 becomes 2 (10 binary), -1 becomes 3 (11 binary)
     *   2 becomes 4 (100 binary), -2 becomes 5 (101 binary)
     *
     * @param integer $value
     *
     * @return integer
     */
    private static function toVLQSigned($value): int
    {
        if ($value < 0) {
            return ((-$value) << 1) + 1;
        }

        return ($value << 1) + 0;
    }

    private static function encodeDigit($value): string
    {
        return self::$encodingMap[$value];
    }

}
