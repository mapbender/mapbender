<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Form\Type\ExtentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MapAdminType extends AbstractType implements DataTransformerInterface
{
    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }


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
                'attr' => array(
                    'class' => 'input inputWrapper choiceExpandedSortable',
                ),
            ))
            ->add('tileSize', NumberType::class, array(
                'required' => false,
                'label' => 'Tile size',
            ))
            ->add('srs', TextType::class, array(
                'label' => 'SRS',
            ))
            ->add('base_dpi', NumberType::class, $this->createInlineHelpText([
                'label' => 'mb.manager.admin.map.base_dpi',
                'help' => 'mb.manager.admin.map.base_dpi.help',
            ], $this->trans))
            ->add('extent_max', ExtentType::class, $this->createInlineHelpText([
                'label' => 'mb.manager.admin.map.max_extent',
                'help' => 'mb.manager.admin.map.max_extent.help',
            ], $this->trans))
            ->add('extent_start', ExtentType::class, $this->createInlineHelpText([
                'label' => 'mb.manager.admin.map.start_extent',
                'help' => 'mb.manager.admin.map.start_extent.help',
            ], $this->trans))
            ->add('fixedZoomSteps', CheckboxType::class, array(
                'label' => 'mb.core.map.admin.fixedZoomSteps',
                'required' => false,
            ))
            ->add('scales', TextType::class, array(
                'label' => 'Scales (csv)',
                'required' => true,
            ))
            ->add('otherSrs', TextType::class, array(
                'label' => 'Other SRS',
                'required' => false,
            ))
        ;
    }

    public function transform($value)
    {
        if ($value) {
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
