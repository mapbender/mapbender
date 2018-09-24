<?php


namespace Mapbender\CoreBundle\Form\Type\Application;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Choice selector for SourceInstanceLayer in the scope of one Application.
 * Application entity must be passed in options under 'application'.
 * Submit value is the SourceInstanceLayer's id.
 *
 * NOTE: this selector generates grouped choices (sourceinstance => layers in instance)
 *       which cannot be rendered with FOM's global form theme replacement in place.
 *       This is a FOM issue, not a Mapbender issue. You can work around this by using
 *       a feature-complete form theme, e.g. (in your form twig):
 *       {% form_theme form 'bootstrap_3_layout.html.twig' %}
 *
 *       See https://symfony.com/doc/2.8/form/form_customization.html for more information.
 */
class SourceInstanceLayerSelectorType extends AbstractType
{
    public function getName()
    {
        return 'application_source_instance_layer_selector';
    }

    public function getParent()
    {
        return 'choice';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // thank you PHP for not allowing "use $this" in a lambda
        $self = $this;
        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($self) {
                return $self->getOptionGroups($options['application']);
            },
            'choices_as_values' => true,
            'placeholder' => null,
            'required' => true,
        ));
        $resolver->setRequired(array(
            'application'
        ));
    }

    protected function getOptionGroups(Application $application)
    {
        $groups = array();
        $flat = array();
        foreach ($application->getLayersets() as $layerset) {
            $lsTitle = $layerset->getTitle();
            foreach ($layerset->getInstances() as $instance) {
                $instTitle = $instance->getTitle();
                $groupName = "{$lsTitle}: {$instTitle}";
                $groupMembers = array();
                foreach ($instance->getRootlayer()->getSublayer() as $layer) {
                    $groupMembers[$layer->getTitle()] = $layer->getId();;
                }
                asort($groupMembers);
                $groups[$groupName] = $groupMembers;
                $flat = array_replace($flat, $groupMembers);
            }
        }
        ksort($groups);
        return $groups;
    }
}
