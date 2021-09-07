<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Form\DataMapperInterface;

class RegionPropertiesMapper implements DataMapperInterface
{
    /**
     * @param Collection|Selectable|RegionProperties[] $viewData
     * @param \Symfony\Component\Form\FormInterface[]|\Traversable $forms
     */
    public function mapDataToForms($viewData, $forms)
    {
        foreach ($forms as $form) {
            if ($form->getConfig()->getMapped()) {
                $regionName = $form->getName();
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('name', $regionName))
                ;
                $rpEntity = $viewData->matching($criteria)->first() ?: null;
                $application = $form->getParent()->getParent()->getData();
                if (!$rpEntity) {
                    $rpEntity = $this->createDefault($application, $regionName);
                } else {
                    $this->mergeDefaults($rpEntity, $application, $regionName);
                }
                $form->setData($rpEntity);
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * @param Collection|Selectable|RegionProperties[] $viewData
     * @param \Symfony\Component\Form\FormInterface[]|\Traversable $forms
     */
    public function mapFormsToData($forms, &$viewData)
    {
        foreach ($forms as $form) {
            if ($form->getConfig()->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $formData = $form->getData();
                if (!$viewData->contains($formData)) {
                    $viewData->add($formData);
                }
            }
        }
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return RegionProperties
     */
    protected function createDefault($application, $regionName)
    {
        $rpEntity = new RegionProperties();
        $rpEntity->setApplication($application);
        $rpEntity->setName($regionName);
        $regionValues = $this->getRegionDefaults($application, $regionName);
        if ($regionValues) {
            $rpEntity->setProperties($regionValues);
        }
        return $rpEntity;
    }

    protected function mergeDefaults(RegionProperties $props, Application $application, $regionName)
    {
        $defaults = $this->getRegionDefaults($application, $regionName);
        $merged = ($props->getProperties() ?: array()) + $defaults;
        $props->setProperties($merged);
    }

    protected function getRegionDefaults(Application $application, $regionName)
    {
        /** @var Template|string $templateClass */
        $templateClass = $application->getTemplate();
        $defaults = $templateClass::getRegionPropertiesDefaults($regionName);
        unset($defaults['label']);
        return $defaults;
    }
}
