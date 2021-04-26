<?php


namespace Mapbender\Component;


use Symfony\Component\HttpFoundation\File\File;

/**
 * Extension of Symfony File to provide some basic component asset mimetypes even in absence
 * of PHP Fileinfo extension.
 */
class AutoMimeResponseFile extends File
{
    /** @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types */
    protected static $extensionMimeMap = array(
        // textual
        'css' => 'text/css',
        'js' => 'text/javascript',
        // Font types
        'otf' => 'font/otf',
        'ttf' => 'font/ttf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        // Image types
        'bmp' => 'image/bmp',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.ico',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    );

    public function getMimeType()
    {
        if ($mime = $this->guessMimeTypeFromExtension($this->getExtension())) {
            return $mime;
        } else {
            return parent::getMimeType();
        }
    }

    public static function guessMimeTypeFromExtension($extension)
    {
        $extension = \strtolower($extension);
        if ($extension && !empty(static::$extensionMimeMap[$extension])) {
            $baseMime = static::$extensionMimeMap[$extension];
            if (\preg_match('#^text/#', $baseMime)) {
                return $baseMime . '; charset=UTF-8';
            } else {
                return $baseMime;
            }
        }
        return false;
    }
}
