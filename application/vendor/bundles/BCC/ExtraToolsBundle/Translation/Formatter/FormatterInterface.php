<?php

namespace BCC\ExtraToolsBundle\Translation\Formatter;

/**
 * Interface for formatters
 */
interface FormatterInterface
{
    /**
     * Generates a string representation of the message format.
     *
     * @param $messages array
     * @return string
     */
    public function format(array $messages);

}
