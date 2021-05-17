<?php


namespace Mapbender\PrintBundle\Component\Plugin;

use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends Print to inject Digitizer feature data into print template text fields
 *
 * @todo: This belongs in the Digitizer bundle (separate repository); included here only for historical reasons.
 *        The equivalent non-plugin implementation shipped with Mapbender itself, not as part of Digitizer.
 */
class DigitizerPrintPlugin implements TextFieldPluginInterface
{
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    protected $featureTypeParamName;

    /**
     * @param ContainerInterface $container
     * @param $featureTypeParamName
     */
    public function __construct(ContainerInterface $container, $featureTypeParamName)
    {
        $this->container = $container;
        $this->featureTypeParamName = $featureTypeParamName;
    }

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
