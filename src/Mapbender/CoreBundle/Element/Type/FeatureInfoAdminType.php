<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FeatureInfoAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displayType', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    'Tabs' => 'tabs',
                    'Accordion' => 'accordion',
                ),
            ))
            ->add('autoActivate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.autoActivate',
            ))
            ->add('printResult', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('deactivateOnClose', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.deactivateonclose',
            ))
            ->add('onlyValid', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.onlyvalid',
            ))
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => true,
            ))
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => true,
            ))
            ->add('maxCount', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 100,
                ),
            ))
            ->add('highlighting', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.highlighting',
            ))
            ->add('defaultStyle', PaintType::class, array(
                'label' => 'mb.core.admin.featureinfo.label.default_group',
                'inherit_data' => true,
                'fieldNameFillColor' => 'fillColorDefault',
                'fieldNameStrokeColor' => 'strokeColorDefault',
                'fieldNameStrokeWidth' => 'strokeWidthDefault',
            ))
            ->add('hoverStyle', PaintType::class, array(
                'label' => 'mb.core.admin.featureinfo.label.hover_group',
                'inherit_data' => true,
                'fieldNameFillColor' => 'fillColorHover',
                'fieldNameStrokeColor' => 'strokeColorHover',
                'fieldNameStrokeWidth' => 'strokeWidthHover',
            ))
        ;
    }
}
