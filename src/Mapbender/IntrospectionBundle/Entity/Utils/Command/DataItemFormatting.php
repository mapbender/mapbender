<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Container for structuring options relating to DataItem toArray() conversion. This is used to hold and pass around
 * settings while dumping DataItems / DataTreeNodes to Json or Yaml.
 *
 */
class DataItemFormatting
{
    /** @var string */
    public $nameKey;
    /** @var bool */
    public $hoistIds;
    /** @var bool */
    protected $embedFlags;
    /** @var string|false */
    protected $flagSubkey;


    /**
     * @param string $nameKey key to map the DataItem's name to in the toArray() return; default "name"
     * @param bool $hoistIds true to bake ids into the "name" (or renamed) value from toArray()
     * @param string|bool $flagEmbedding true to inline flags into the top-level return from DataItem->toArray. Any
     *                                 non-empty string to place them into a sub-array with that key. False to not
     *                                 emit them at all.
     */
    public function __construct($nameKey = 'name', $hoistIds = false, $flagEmbedding = true)
    {
        $this->nameKey = $nameKey;
        $this->hoistIds = $hoistIds;
        $this->setFlagEmbeddingMode($flagEmbedding);
    }

    /**
     * @param string|bool $flagEmbedding true to inline flags into the top-level return from DataItem->toArray. Any
     *                                 non-empty string to place them into a sub-array with that key. False to not
     *                                 emit them at all.
     */
    public function setFlagEmbeddingMode($flagEmbedding = true)
    {
        if (!$flagEmbedding) {
            $this->embedFlags = false;
            $this->flagSubkey = false;
        } else {
            if ($flagEmbedding === true) {
                $this->embedFlags = true;
                $this->flagSubkey = false;
            } else {
                $this->embedFlags = false;
                $this->flagSubkey = strval($flagEmbedding);
            }
        }
    }

    /**
     * Depending on settings, either supress, return key-swapped or fold into a sub-array the given $flagsIn
     * Caller should replace the return value directly into its representing array (same node level as "name").
     *
     * @param array $flagsIn
     * @return array
     */
    public function mungeFlags($flagsIn)
    {
        if ($this->embedFlags) {
            return $flagsIn;
        } elseif ($this->flagSubkey && $flagsIn) {
            return array(
                $this->flagSubkey => $flagsIn,
            );
        } else {
            return array();
        }
    }

    public function apply($id, $name, $flags)
    {
        $rv = array();
        if (!$this->hoistIds) {
            $rv['id'] = $id;
        }
        $rv[$this->nameKey] = $name;
        $rv += $this->mungeFlags($flags);
        return $rv;
    }
}
