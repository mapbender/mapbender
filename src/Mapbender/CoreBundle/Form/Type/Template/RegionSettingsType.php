<?php


namespace Mapbender\CoreBundle\Form\Type\Template;


use Mapbender\CoreBundle\Entity\RegionProperties;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;

class RegionSettingsType extends AbstractType implements DataMapperInterface
{
    public function getBlockPrefix()
    {
        return 'region_settings';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper($this);
    }

    /**
     * @param RegionProperties|null $viewData
     * @param \Symfony\Component\Form\FormInterface[]|\Traversable $forms
     */
    public function mapDataToForms($viewData, $forms)
    {
        if (!$viewData) {
            return;
        }

        $props = $viewData->getProperties();
        foreach ($forms as $form) {
            $propertyName = $form->getPropertyPath()->getElement(0);
            if (array_key_exists($propertyName, $props) && $form->getConfig()->getMapped()) {
                $form->setData($props[$propertyName]);
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * @param \Symfony\Component\Form\FormInterface[]|\Traversable $forms
     * @param RegionProperties $viewData
     */
    public function mapFormsToData($forms, &$viewData)
    {
        if (null === $viewData) {
            return;
        }
        if (is_array($viewData)) {
            die(var_export($viewData, true));
        }

        $props = $viewData->getProperties();
        foreach ($forms as $form) {
            $config = $form->getConfig();
            if ($config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $propertyName = $form->getPropertyPath()->getElement(0);
                $props[$propertyName] = $form->getData();
            }
        }
        $viewData->setProperties($props);
    }
}
