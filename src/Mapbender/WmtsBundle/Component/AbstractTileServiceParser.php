<?php


namespace Mapbender\WmtsBundle\Component;


abstract class AbstractTileServiceParser
{
    /**
     * @param \DOMElement $parent
     * @param string $localName
     * @return \DOMElement[]
     */
    protected static function getChildNodesByTagName(\DOMElement $parent, $localName)
    {
        $children = array();
        foreach ($parent->childNodes ?: array() as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $localName) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * @param \DOMElement $parent
     * @param string $localName
     * @return \DOMElement|null
     */
    protected static function getFirstChildNode(\DOMElement $parent, $localName)
    {
        $matches = static::getChildNodesByTagName($parent, $localName);
        return count($matches) && ($matches[0] instanceof \DOMElement)
            ? $matches[0]
            : null
        ;
    }

    protected static function getFirstChildNodeText(\DOMElement $parent, $localName, $default = null)
    {
        $node = static::getFirstChildNode($parent, $localName);
        return $node ? ($node->textContent ?: $default) : $default;
    }
}
