<?php

namespace BCC\ExtraToolsBundle\Translation\Loader;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * PotFileLoader loads translations from Gettext Portable Object Templates (.pot) files
 * returning an array of translations.
 *
 * @author Moiśes Maciá <mmacia@gmail.com>
 */
class PotFileLoader extends ArrayLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $messages = array();
        $catalogue = new MessageCatalogue($locale);
        $catalogue->add($resource, $domain);

        $messages = $this->parse($resource);

        $catalogue = parent::load($messages, $locale, $domain);
        $catalogue->addResource(new FileResource($resource));

        return $catalogue;
    }

    /**
     * Parse a POT file extracting translation units
     * @param string $resource Path to .pot file
     * @return array
     */
    protected function parse($resource)
    {
        $fp = fopen($resource, 'r');
        $messages = array();
        $pair = array(); // translation unit pair: id => translated string

        while (!feof($fp)) {
            $line = fgets($fp);

            if (strncmp($line, 'msgid', strlen('msgid')) === 0) { // is a message id
                $str = $this->normalize(substr($line, strlen('msgid')+1, strlen($line)));
                if (!empty($str)) {
                    $pair[] = $str;
                }
            } elseif (strncmp($line, 'msgstr', strlen('msgstr')) === 0) { // is a translation
                $str = $this->normalize(substr($line, strlen('msgstr')+1, strlen($line)));
                if (!empty($pair)) {
                    $pair[] = $str;
                }
            }

            if (count($pair) == 2) { // we have extracted a translation unit
                $messages[$pair[0]] = $pair[1];
                $pair = array();
            }
        }

        fclose($fp);
        return $messages;
    }

    /**
     * Normalize strings
     * @return string
     */
    protected function normalize($str)
    {
        $str = str_replace('\"', '$$', trim($str)); // replace slashed quotes
        $str = substr($str, strpos($str, '"')+1, strlen($str)); // remove first quote
        $str = substr($str, 0, stripos($str, '"')); // remove last quote

        return str_replace('$$', '"', $str); // restore replaced quotes
    }
}
