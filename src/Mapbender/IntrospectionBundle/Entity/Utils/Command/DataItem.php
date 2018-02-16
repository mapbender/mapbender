<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Trivial model of an object id and a displayable name plus some console output styling awareness.
 *
 * This is the "leaf node" in a DataGroup.
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
    protected $modifiers;

    /**
     * @param string $id
     * @param string $name
     * @param string[] $modifiers
     * @param string|null $wrapStyle Symfony OutputFormatter style reference, try "comment", "info", "error" etc.
     */
    public function __construct($id, $name, $modifiers = array(), $wrapStyle = null)
    {
        $this->id = strval($id);
        $this->name = strval($name);
        $this->modifiers = array_unique($modifiers) ?: array();
        $this->setWrapStyle($wrapStyle);
    }

    /**
     * Wrap style will be applied around the whole displayable output (id plus name) in direct CLI rendering mode,
     * but is ignored in array conversion (yaml / json).
     *
     * @param string|null $wrapStyle Symfony OutputFormatter style reference, try "comment", "info", "error" etc.
     */
    public function setWrapStyle($wrapStyle = null)
    {
        $this->wrapStyle = $wrapStyle;
    }

    /**
     * Add a modifier. This should be a short displayable text.
     * In table rendering, we put all modifiers into brackets after the item name, and apply <note> highlighting.
     * For array / yaml / json representation, these modifiers will be placed into the node array structure directly.
     *
     * @param string $modifier
     */
    public function addModifier($modifier)
    {
        $this->modifiers = array_unique(array_merge($this->modifiers, (array)$modifier));
    }

    /**
     * Add a modifier and / or style if $predicate is true. Otherwise do nothing.
     *
     * @param boolean $predicate
     * @param string $style
     * @param string|null $modifier optional
     */
    public function applyStyleIf($predicate, $style, $modifier)
    {
        if ($predicate) {
            $this->setWrapStyle($style);
            if ($modifier) {
                $this->addModifier($modifier);
            }
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
        if ($this->modifiers && $includeModifiers) {
            $baseText .= ' <note>(' . implode(',', $this->modifiers) . ')</note>';
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
    public function toArray(DataItemFormatting $format, $_unusedLabels = null)
    {
        return $format->apply($this->id, $this->name, $this->modifiers);
    }
}
