<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Trivial model of an object id and a displayable name plus some console output styling awareness.
 *
 * This is the "leaf node" in a DataTreeNode.
 *
 * @package Mapbender\IntrospectionBundle\Entity\Utils\Command
 */
class DataItem
{
    /** @var string */
    protected $id;
    /** @var string  */
    protected $name;
    /** @var string */
    protected $wrapStyle;
    /** @var string[] */
    protected $note;
    /** @var string[] */
    protected $flags;

    /**
     * @param string $id
     * @param string $name
     * @param string[] $flags
     * @param string|null $wrapStyle Symfony OutputFormatter style reference, try "comment", "info", "error" etc.
     */
    public function __construct($id, $name, $flags = array(), $wrapStyle = null)
    {
        $this->id = strval($id);
        $this->name = strval($name);
        $this->flags = $flags ?: array();
        $this->setWrapStyle($wrapStyle);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Wrap style will be applied around the whole displayable output (id plus name) in direct CLI rendering mode,
     * but is ignored in array conversion (yaml / json).
     *
     * @param string|null $wrapStyle Symfony OutputFormatter style reference, try "comment", "info", "error" etc.
     * @param string|null $explanation will be displayed in brackets to explain the highlighting
     */
    public function setWrapStyle($wrapStyle = null, $explanation = null)
    {
        $this->wrapStyle = $wrapStyle;
        if ($wrapStyle && $explanation) {
            $this->setNote($explanation);
        }
    }

    /**
     * @param string|null $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Add a flag, named with a value. This name => value association will be exported raw in ->toArray mode.
     * For table rendering, the flag is only checked for truthiness. A highlight style is added for truthy or falsy
     * (or theoretically both), and an explanation can be given that will be displayed after the "name", in brackets,
     * which should very briefly identify the reason why the item is highlighted. This explanation is only displayed
     * if there is any highlighting.
     *
     * @param string $name
     * @param mixed $rawValue
     * @param string|null $truthyStyle applied in table cell if $rawValue truthy
     * @param string|null $falsyStyle applied in table cell if $rawValue falsy
     * @param string|null $explanation displayed in table cell if any style applied
     */
    public function addFlag($name, $rawValue, $truthyStyle, $falsyStyle, $explanation)
    {
        $this->flags[$name] = $rawValue;
        if ($rawValue && $truthyStyle) {
            $this->setWrapStyle($truthyStyle, $explanation);
        } elseif (!$rawValue && $falsyStyle) {
            $this->setWrapStyle($falsyStyle, $explanation);
        }
    }

    /**
     * Format for CLI display, applying OutputFormatter-conformant styling. This will bake the id and name into a single
     * string and apply the wrapStyle around the whole of the result.
     *
     * @param bool|null $includeId prefix with id yes / no or automatic (id not empty)
     * @param bool $includeModifiers
     * @return string
     */
    public function toDisplayable($includeId = null, $includeModifiers = true)
    {
        if ($includeId === null) {
            $includeId = !empty($this->id) || is_numeric($this->id);
        }
        if ($includeId) {
            $baseText = "{$this->id}: {$this->name}";
        } else {
            $baseText = $this->name;
        }
        if ($this->note && $includeModifiers) {
            $baseText .= ' <note>(' . implode(',', (array)$this->note) . ')</note>';
        }

        if ($this->wrapStyle) {
            return "<{$this->wrapStyle}>{$baseText}</{$this->wrapStyle}>";
        } else {
            return $baseText;
        }
    }

    /**
     * Format for CLI table display. This will always return a 2D matrix of cell contents, even on the leaf node.
     *
     * @return string[][]
     */
    public function toGrid()
    {
        return array(array($this->toDisplayable()));
    }

    /**
     * Back to array with keys id and name. Style has no effect. This is for rendering YAML or JSON.
     *
     * @param DataItemFormatting $format
     *
     * @return string[]
     */
    public function toArray(DataItemFormatting $format)
    {
        return $format->apply($this->id, $this->name, $this->flags);
    }
}
