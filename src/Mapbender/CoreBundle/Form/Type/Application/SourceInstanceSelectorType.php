<?php


namespace Mapbender\CoreBundle\Form\Type\Application;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Choice selector for SourceInstances in the scope of one Application.
 * Application entity must be passed in options under 'application'.
 * Submit value is the SourceInstance's id.
 */
class SourceInstanceSelectorType extends AbstractType
{
    public function getName()
    {
        return 'application_source_instance_selector';
    }

    public function getParent()
    {
        return 'choice';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // thank you PHP for not allowing "use $this" in a lambda
        $self = $this;
        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($self) {
                return $self->getOptions($options['application']);
            },
            'choices_as_values' => true,
            'placeholder' => null,
            'required' => true,
        ));
        $resolver->setRequired(array(
            'application'
        ));
    }

    protected function getOptions(Application $application)
    {
        $options = array();
        foreach ($application->getLayersets() as $layerset) {
            $lsTitle = $layerset->getTitle();
            foreach ($layerset->getInstances() as $instance) {
                $instTitle = $instance->getTitle();
                $label = "{$lsTitle}: {$instTitle}";
                $value = $instance->getId();
                $options[$label] = $value;
            }
        }
        ksort($options);
        return $options;
    }
}
