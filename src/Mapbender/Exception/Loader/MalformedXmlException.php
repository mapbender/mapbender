<?php


namespace Mapbender\Exception\Loader;


class MalformedXmlException extends SourceLoaderException
{
    /** @var string */
    protected $content;

    public function __construct($content, $message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->content = $content ?: '';
    }

    public function getContent($squashLines = true, $normalizeWhitespace = true, $charLimit = 50)
    {
        $content = $this->content;
        if ($squashLines) {
            $content = \preg_replace('#[\n\r]+#mu', ' ', $content);
        }
        if ($normalizeWhitespace) {
            $content = \preg_replace('#\s+#mu', ' ', $content);
        }
        if ($charLimit > 0) {
            $content = \mb_substr($content, 0, $charLimit);
        }
        return $content;
    }
}
