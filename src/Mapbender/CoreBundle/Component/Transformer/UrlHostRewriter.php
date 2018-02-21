<?php


namespace Mapbender\CoreBundle\Component\Transformer;
use Mapbender\CoreBundle\Utils\UrlUtil;


/**
 * A Transformer that processes urls and rewrites the host
 */
class UrlHostRewriter extends ValueTransformerBase
{
    /** @var string */
    protected $to;
    /** @var string|null  */
    protected $from;

    /**
     * @param string $to new host name
     * @param string|null $from old host name (optional); if given, only replace if previous hostname equals $from
     * @param bool $once see parent
     */
    public function __construct($to, $from = null, $once = true)
    {
        parent::__construct($once);
        $this->to = $to;
        $this->from = $from;
    }

    /**
     * @param string $value
     * @return string
     */
    public function transformOnce($value)
    {
        return UrlUtil::replaceHost($value, $this->to, $this->from);
    }
}
