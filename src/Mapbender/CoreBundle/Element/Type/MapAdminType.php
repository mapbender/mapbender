<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder
            ->add('layersets', 'Mapbender\CoreBundle\Element\Type\LayersetAdminType', array(
                'application' => $options['application'],
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'auto_initialize' => false,
                'attr' => array(
                    'data-sortable' => 'choiceExpandedSortable',
                ),
            ))
            ->add('dpi', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'label' => 'DPI',
            ))
            ->add('tileSize', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'required' => false,
                'label' => 'Tile size',
            ))
            ->add('srs', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'SRS',
            ))
            ->add('extent_max', 'Mapbender\CoreBundle\Form\Type\ExtentType', array(
                'label' => 'mb.manager.admin.map.max_extent',
                'property_path' => '[extents][max]',
            ))
            ->add('extent_start', 'Mapbender\CoreBundle\Form\Type\ExtentType', array(
                'label' => 'mb.manager.admin.map.start_extent',
                'property_path' => '[extents][start]',
            ))
            ->add('scales', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Scales (csv)',
                'required' => true,
            ))
            ->add('otherSrs', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
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
