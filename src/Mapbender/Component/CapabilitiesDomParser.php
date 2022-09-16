<?php
/** @noinspection PhpComposerExtensionStubsInspection */


namespace Mapbender\Component;

/**
 * Collection of convenience methods for extracting common XML
 * capabilities structures using ext-dom \DOMElement api.
 */
class CapabilitiesDomParser
{
    /**
     * Convenience method to get direct child elements (not text nodes)
     * by local name (namespace prefixes not required).
     *
     * @param \DOMElement $parent
     * @param string $localName
     * @return \DOMElement[]
     */
    public static function getChildNodesByTagName(\DOMElement $parent, $localName)
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
     * Convenience method to get the first direct child element by local name.
     * Elements that must / optionally can appear exactly once in their
     * scope are very common in misc OGC capabilities formats.
     *
     * @param \DOMElement $parent
     * @param string $localName
     * @return \DOMElement|null
     */
    public static function getFirstChildNode(\DOMElement $parent, $localName)
    {
        $matches = static::getChildNodesByTagName($parent, $localName);
        return count($matches) && ($matches[0] instanceof \DOMElement)
            ? $matches[0]
            : null
        ;
    }

    /**
     * Convenience method to get the full text content under the the
     * first direct child element by local name.
     * Elements that must / optionally can appear exactly once in their
     * scope are very common in misc OGC capabilities formats.
     *
     * NOTE: this is CDATA-safe
     *
     * @param \DOMElement $parent
     * @param string $localName
     * @param null|mixed $default
     * @return string|null|mixed
     */
    public static function getFirstChildNodeText(\DOMElement $parent, $localName, $default = null)
    {
        $node = static::getFirstChildNode($parent, $localName);
        return $node ? ($node->textContent ?: $default) : $default;
    }
}
