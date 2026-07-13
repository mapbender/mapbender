<?php


namespace Mapbender\Component\Transformer;


class StringReplaceTransformer implements OneWayTransformer
{
    /**
     * @param string[] $replacements before => after
     * @param bool $caseSensitive
     */
    public function __construct(protected $replacements, protected $caseSensitive = true)
    {
    }

    public function process($x)
    {
        if ($this->caseSensitive) {
            return strtr($x, $this->replacements);
        } else {
            $rv = $x;
            foreach ($this->replacements as $from => $to) {
                $rv = str_ireplace($from, $to, $rv);
            }
            return $rv;
        }
    }
}
