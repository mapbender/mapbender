<?php
/** @noinspection PhpComposerExtensionStubsInspection */


namespace Mapbender\Component;

use Mapbender\CoreBundle\Entity\Contact;

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
    public static function getChildNodesByTagName(\DOMElement $parent, $localName): array
    {
        $children = [];
        foreach ($parent->childNodes ?: [] as $child) {
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
    public static function getFirstChildNode(\DOMElement $parent, $localName): ?\DOMElement
    {
        $matches = static::getChildNodesByTagName($parent, $localName);
        return count($matches) && ($matches[0] instanceof \DOMElement)
            ? $matches[0]
            : null;
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
        if (!$node || $node->textContent === '' || $node->textContent === null) return $default;
        return $node->textContent;
    }

    /**
     * Parses a ContactInformation node. Appears in WMS and TMS.
     *
     * @param \DOMElement $element
     * @return Contact
     */
    protected function parseContactInformation(\DOMElement $element): Contact
    {
        $personPrimaryEl = static::getFirstChildNode($element, 'ContactPersonPrimary');
        $addressEl = static::getFirstChildNode($element, 'ContactAddress');
        $contact = new Contact();
        if ($personPrimaryEl) {
            $contact->setPerson(static::getFirstChildNodeText($personPrimaryEl, 'ContactPerson'));
            $contact->setOrganization(static::getFirstChildNodeText($personPrimaryEl, 'ContactOrganization'));
        }
        $contact->setPosition(static::getFirstChildNodeText($element, 'ContactPosition'));
        if ($addressEl) {
            $contact->setAddressType(static::getFirstChildNodeText($addressEl, 'AddressType'));
            $contact->setAddress(static::getFirstChildNodeText($addressEl, 'Address'));
            $contact->setAddressCity(static::getFirstChildNodeText($addressEl, 'City'));
            $contact->setAddressStateOrProvince(static::getFirstChildNodeText($addressEl, 'StateOrProvince'));
            $contact->setAddressPostCode(static::getFirstChildNodeText($addressEl, 'PostCode'));
            $contact->setAddressCountry(static::getFirstChildNodeText($addressEl, 'Country'));
        }
        $contact->setVoiceTelephone(static::getFirstChildNodeText($element, 'ContactVoiceTelephone'));
        $contact->setFacsimileTelephone(static::getFirstChildNodeText($element, 'ContactFacsimileTelephone'));
        $contact->setElectronicMailAddress(static::getFirstChildNodeText($element, 'ContactElectronicMailAddress'));
        return $contact;
    }
}
