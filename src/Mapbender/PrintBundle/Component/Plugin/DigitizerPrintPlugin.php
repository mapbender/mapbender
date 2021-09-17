<?php


namespace Mapbender\PrintBundle\Component\Plugin;

/**
 * Extends Print to inject Digitizer feature data into print template text fields
 *
 * @todo: This belongs in the Digitizer bundle (separate repository); included here only for historical reasons.
 *        The equivalent non-plugin implementation shipped with Mapbender itself, not as part of Digitizer.
 */
class DigitizerPrintPlugin implements TextFieldPluginInterface
{
    public function getDomainKey()
    {
        return 'digitizer';
    }

    public function getTextFieldContent($fieldName, $jobData)
    {
        if (isset($jobData['digitizer_feature']) && preg_match("/^feature./", $fieldName)) {
            $attributes = $jobData['digitizer_feature'] ?: array();
            $attributeName = substr(strrchr($fieldName, "."), 1);
            if (!empty($attributes[$attributeName])) {
                return $attributes[$attributeName];
            }
        }
        return null;
    }
}
