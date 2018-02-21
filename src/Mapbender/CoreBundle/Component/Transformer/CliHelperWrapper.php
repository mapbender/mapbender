<?php


namespace Mapbender\CoreBundle\Component\Transformer;

/**
 * A transformer that delegates all actual transformation work to any other transformer, but keeps track
 * of changes and progress and can communicate them back via callbacks.
 */
class CliHelperWrapper extends ValueTransformerBase
{
    /** @var ValueTransformerBase */
    protected $transformer;

    /** @var null|callable called once with no arguments for every transform() invocation */
    protected $changeCallback;


    public function __construct(ValueTransformerBase $transformer, $changeCallback = null)
    {
        parent::__construct(true);
        $this->transformer = $transformer;
        $this->changeCallback = $changeCallback;
    }

    public function transformOnce($value)
    {
        return $this->transformer->transform($value);
    }

    public function compare($a, $b)
    {
        return $this->transformer->compare($a, $b);
    }

    public function transform($value)
    {
        $before = $value;
        $after = $this->transformOnce($value);
        if ($this->changeCallback && !$this->transformer->compare($before, $after)) {
            ($this->changeCallback)($before, $after);
        }
        return $after;
    }
}
