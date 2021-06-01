<?php


namespace Mapbender\CoreBundle\Form\Type\Application;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Form\Type\RelatedObjectChoiceType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Choice selector for SourceInstances in the scope of one Application.
 * Application entity must be passed in options under 'application'.
 * Submit value is the SourceInstance's id.
 */
class SourceInstanceSelectorType extends RelatedObjectChoiceType implements DataTransformerInterface
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(array(
            'application',
        ));
        // no use ($this) on lambdas in PHP5.4
        $self = $this;
        $resolver->setDefaults(array(
            'label_with_layerset_prefix' => true,
            'choice_label' => function(Options $options) use ($self) {
                if ($options['label_with_layerset_prefix']) {
                    $instanceIdToLayersetMap = $self->getInstanceIdToLayersetMap($options['application']);

                    return function($choice) use ($instanceIdToLayersetMap) {
                        /** @var SourceInstance $choice*/
                        $layerset = $instanceIdToLayersetMap[$choice->getId()];
                        $label = ltrim($layerset->getTitle() . ': ', ' :');
                        $label .= $choice->getTitle();
                        return $label;
                    };
                } else {
                    return 'title';
                }
            },
            'parent_object' => function(Options $options) {
                return $options['application'];
            },
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    protected function getRelatedObjectCollection($parentObject)
    {
        $instances = array();
        /** @var Application $parentObject */
        foreach ($parentObject->getLayersets() as $layerset) {
            foreach ($layerset->getCombinedInstances() as $instance) {
                $instances[$instance->getId()] = $instance;
            }
        }
        ksort($instances);
        return $instances;
    }

    public function reverseTransform($value)
    {
        if ($value && is_object($value)) {
            return $value->getId();
        } elseif (is_array($value)) {
            $valueOut = array();
            foreach ($value as $k => $v) {
                $valueOut[$k] = $this->reverseTransform($v);
            }
            return $valueOut;
        }
        return $value ?: null;
    }

    public function transform($value)
    {
        return $value;
    }

    /**
     * @param Application $application
     * @return Layerset[] keyed on source instance id
     */
    protected function getInstanceIdToLayersetMap(Application $application)
    {
        $map = array();
        foreach ($application->getLayersets() as $layerset) {
            foreach ($layerset->getCombinedInstances() as $instance) {
                $map[$instance->getId()] = $layerset;
            }
        }
        return $map;
    }
}
