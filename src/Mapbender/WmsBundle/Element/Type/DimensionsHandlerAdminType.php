<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\WmsBundle\Component\DimensionInst;
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
            'element' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $application = $options["application"];
        $element = $options["element"];
        $dimensions = array();
        if ($element !== null && $element->getId() !== null) {
            $configuration = $element->getConfiguration();
            if (!empty($configuration['target'])) {
                $mapId = $configuration['target'];
                $dimensions = $this->collectDimensions($application, intval($mapId));
            }
        }
        $builder
            ->add('tooltip', 'text', array(
                'required' => false,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
        ;
        if ($dimensions) {
            $builder
                ->add('dimensionsets', "collection", array(
                    'type' => new DimensionSetAdminType(),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'auto_initialize' => false,
                    'options' => array(
                        'dimensions' => $dimensions,
                    ),
                ))
            ;
        }
    }

    /**
     * @param Application $application
     * @param int $mapId
     * @return DimensionInst[]
     */
    protected function collectDimensions($application, $mapId)
    {
        $dimensions = array();
        foreach ($application->getElements() as $appl_element) {
            if ($appl_element->getId() === $mapId) {
                $mapconfig = $appl_element->getConfiguration();
                if (!isset($mapconfig['layersets']) && isset($mapconfig['layerset'])
                    && $mapconfig['layerset'] === null && is_int($mapconfig['layerset'])) {
                    $mapconfig['layersets'] = array(intval($mapconfig['layerset']));
                }
                foreach ($application->getLayersets() as $layerset_) {
                    if (in_array($layerset_->getId(), $mapconfig['layersets'])) {
                        foreach ($layerset_->getInstances() as $instance) {
                            if ($instance instanceof WmsInstance) {
                                foreach ($instance->getDimensions() ?: array() as $ix => $dimension) {
                                    /** @var DimensionInst $dimension */
                                    $key = "{$instance->getId()}-{$ix}";
                                    $dimension->id = $key;
                                    $dimensions[$key] = $dimension;
                                }
                            }
                        }
                        break;
                    }
                }
                break;
            }
        }
        return $dimensions;
    }
}
