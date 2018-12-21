<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Component\ExtendedCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * BaseSourceSwitcher FormType
 */
class BaseSourceSwitcherAdminType extends AbstractType implements ExtendedCollection
{

    public $hasSubForm = true;

    public function isSubForm()
    {
        return $this->hasSubForm;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'basesourceswitcher';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'element' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $application = $options["application"];
        $element = $options["element"];
        $instances = array();
        if ($element !== null && $element->getId() !== null) {
            foreach ($application->getElements() as $appl_element) {
                $configuration = $element->getConfiguration();
                if ($appl_element->getId() === intval($configuration["target"])) {
                    $mapconfig = $appl_element->getConfiguration();
                    foreach ($application->getLayersets() as $layerset_) {
                        if ((isset($mapconfig['layerset'])
                            && strval($mapconfig['layerset']) === strval($layerset_->getId()))
                            || (isset($mapconfig['layersets'])
                            && in_array($layerset_->getId(), $mapconfig['layersets']))) {
                            foreach ($layerset_->getInstances() as $instance) {
                                if ($instance->isBasesource() && $instance->getEnabled()) {
                                    $instances[strval($instance->getId())] = $instance->getTitle();
                                }
                            }
                        }
                    }
                    break;
                }
            }
        }
        $builder
            ->add('title', 'text', array('required' => true))
            ->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element',
                  array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $application,
                'property_path' => '[target]',
                'required' => false));
        if (count($instances) > 0) {
            $builder->add('instancesets', "collection", array(
                'property_path' => '[instancesets]',
                'type' => new InstanceSetAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
                'options' => array('instances' => $instances)
            ));
        }
    }
}
