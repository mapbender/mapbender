<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of WmsLoaderAdminType
 *
 * @author Paul Schmidt
 */
class DimensionsHandlerAdminType extends AbstractType implements ExtendedCollection
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
        return 'dimensionshandler';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        /** @var Application $application */
        $application = $options["application"];
        $element = $options["element"];
        $dimensions = array();
        if ($element !== null && $element->getId() !== null) {
            foreach ($application->getElements() as $appl_element) {
                $configuration = $element->getConfiguration();
                if ($appl_element->getId() === intval($configuration["target"])) {
                    $mapconfig = $appl_element->getConfiguration();
                    if (!isset($mapconfig['layersets']) && isset($mapconfig['layerset'])
                        && $mapconfig['layerset'] === null && is_int($mapconfig['layerset'])) {
                        $mapconfig['layersets'] = array(intval($mapconfig['layerset']));
                    }
                    foreach ($application->getLayersets() as $layerset_) {
                        if (in_array($layerset_->getId(), $mapconfig['layersets'])) {
                            foreach ($layerset_->getInstances() as $instance) {
                                if ($instance instanceof WmsInstance && count($instance->getDimensions()) > 0) {
                                    foreach ($instance->getDimensions() as $dimension) {
                                        $dimensions[$instance->getId() . ""][] = $dimension;
                                    }
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
        $builder->add('tooltip', 'text', array('required' => false))->add('target', 'target_element',
                  array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false));
        if (count($dimensions) > 0) {
            $builder->add('dimensionsets', "collection",
                          array(
                'property_path' => '[dimensionsets]',
                'type' => new DimensionSetAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
                'options' => array('dimensions' => $dimensions)
            ));
        }
    }

}
