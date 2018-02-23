<?php


namespace Mapbender\CoreBundle\Component\Transformer;


/**
 * A thing that changes a value into a different value.
 *
 * Useful as a vistor.
 */
abstract class ValueTransformerBase
{
    /** @var bool */
    protected $once;

    /**
     * @param bool $once to enable repeat runs of the transformer over the same input until it no longer changes
     */
    public function __construct($once = true)
    {
        // force to proper boolean
        $this->once = !!$once;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function transformOnce($value);

    /**
     * Perform change check after transformation. Base class performs PHP `===` check.
     * Child classes should override for types with more complex comparisons.
     *
     * @param mixed $a
     * @param mixed $b should be same type as $a
     * @return bool
     */
    public function compare($a, $b)
    {
        return $a === $b;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function transform($value)
    {
        $valueOut = $this->transformOnce($value);
        if (!$this->once) {
            $same = $this->compare($valueOut, $value);
            while ($same) {
                $value = $valueOut;
                $valueOut = $this->transformOnce($value);
                $same = $this->compare($valueOut, $value);
            };
        }
        return $valueOut;
    }
}
