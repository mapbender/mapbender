<?php


namespace Mapbender\CoreBundle\Component\Transformer;


/**
 * A transformer that modifies strings based on regex search pattern + replacement
 */
class RegexRewriter extends ValueTransformerBase
{
    /** @var string */
    protected $searchPattern;
    /** @var string  */
    protected $replacement;

    /**
     * @param string $searchPattern
     * @param string $replacement
     * @param bool $once see parent
     */
    public function __construct($searchPattern, $replacement, $once = true)
    {
        parent::__construct($once);
        $this->searchPattern = $searchPattern;
        $this->replacement = $replacement;
    }

    /**
     * @param string $value
     * @return string
     */
    public function transformOnce($value)
    {
        return preg_replace($this->searchPattern, $this->replacement, $value);
    }
}
