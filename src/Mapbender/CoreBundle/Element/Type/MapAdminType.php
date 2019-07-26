<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\CoreBundle\Form\Type\ExtentType;

class MapAdminType extends AbstractType implements DataTransformerInterface
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
        /** @var Application $application */
        $application = $options['application'];
        $layersetChoices = array();
        foreach ($application->getLayersets() as $layerset) {
            $layersetChoices[$layerset->getTitle()] = $layerset->getId();
        }
        $builder
            ->add('layersets', 'choice', array(
                'choices' => $layersetChoices,
                'choices_as_values' => true,
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'auto_initialize' => false,
                'attr' => array(
                    'data-sortable' => 'choiceExpandedSortable',
                ),
            ))
            ->add('dpi', 'number', array(
                'label' => 'DPI',
            ))
            ->add('tileSize', 'number', array(
                'required' => false,
                'label' => 'Tile size',
            ))
            ->add('srs', 'text', array(
                'label' => 'SRS',
            ))
            ->add('units', 'choice', array(
                'label' => 'Map units',
                'choices' => array(
                    'degrees' => 'Degrees',
                    'm' => 'Meters',
                    'ft' => 'Feet',
                    'mi' => 'Miles',
                    'inches' => 'Inches',
                ),
            ))
            ->add('extent_max', new ExtentType(), array(
                'label' => 'mb.manager.admin.map.max_extent',
                'property_path' => '[extents][max]',
            ))
            ->add('extent_start', new ExtentType(), array(
                'label' => 'mb.manager.admin.map.start_extent',
                'property_path' => '[extents][start]',
            ))
            ->add('scales', 'text', array(
                'label' => 'Scales (csv)',
                'required' => true,
            ))
            ->add('otherSrs', 'text', array(
                'label' => 'Other SRS',
                'required' => false,
            ))
        ;
    }

    public function transform($value)
    {
        if ($value) {
            if (array_key_exists('layerset', $value)) {
                if (!array_key_exists('layersets', $value)) {
                    // legacy db config, promote to array-form 'layersets'
                    $value['layersets'] = (array)$value['layerset'];
                }
                unset($value['layerset']);
            }
            if (array_key_exists('otherSrs', $value) && is_array($value['otherSrs'])) {
                $value['otherSrs'] = implode(',', array_filter($value['otherSrs']));
            }
            if (array_key_exists('scales', $value) && is_array($value['scales'])) {
                arsort($value['scales'], SORT_NUMERIC);
                $value['scales'] = implode(',', array_filter($value['scales']));
            }

            return $value;
        } else {
            return null;
        }
    }

    public function reverseTransform($value)
    {
        if ($value) {
            if (array_key_exists('otherSrs', $value) && !is_array($value['otherSrs'])) {
                $value['otherSrs'] = array_filter(preg_split('/\s*,\s*/', $value['otherSrs']));
            }
            if (array_key_exists('scales', $value) && !is_array($value['scales'])) {
                $value['scales'] = array_filter(preg_split('/\s*[,;]\s*/', $value['scales']));
                arsort($value['scales'], SORT_NUMERIC);
            }
            return $value;
        } else {
            return null;
        }
    }
}
