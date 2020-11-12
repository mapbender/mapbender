<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Doctrine\Common\Collections\Collection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Symfony\Component\Form\DataTransformerInterface;

class RegionPropertiesTransformer implements DataTransformerInterface
{
    /** @var Application */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function transform($value)
    {
        if ($value) {
            $values = array();
            /** @var Collection|RegionProperties[] $value */
            foreach ($value as $item) {
                $values[$item->getName()] = $item->getProperties();
            }
            return $values;
        }
        return array();
    }

    public function reverseTransform($value)
    {
        $collection = $this->application->getRegionProperties();
        if (!$value) {
            return $collection;
        }

        $missing = array_flip(array_keys($value));
        foreach ($collection as $rprop) {
            $regionName = $rprop->getName();
            if (array_key_exists($regionName, $value)) {
                $formProps = $value[$regionName];
                $mergedProps = array_replace($rprop->getProperties(), $formProps);
                // Legacy quirk: label for sidepane types used to be copied into db but is redundant.
                unset($mergedProps['label']);
                $rprop->setProperties($mergedProps);
                unset($missing[$regionName]);
            }
        }
        foreach (array_keys($missing) as $regionName) {
            $rprop = new RegionProperties();
            $rprop->setName($regionName);
            $rprop->setApplication($this->application);
            $formProps = $value[$regionName];
            // Legacy quirk: label for sidepane types used to be copied into db but is redundant.
            unset($formProps['label']);
            $rprop->setProperties($formProps);
            $this->application->addRegionProperties($rprop);
        }
        return $collection;
    }
}
