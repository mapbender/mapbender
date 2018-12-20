<?php


namespace Mapbender\PrintBundle\Component\Plugin;


interface TextFieldPluginInterface extends PluginBaseInterface
{
    /**
     * Should return the text content for the text field with the given $fieldName, or null
     * if unhandled by this plugin.
     *
     * @param string $fieldName
     * @param array $jobData
     * @return string|null
     */
    public function getTextFieldContent($fieldName, $jobData);
}
