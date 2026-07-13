<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FeatureInfoAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayType', ChoiceType::class, [
                'required' => true,
                'label' => 'mb.core.featureinfo.admin.displaytype',
                'choices' => [
                    'mb.core.featureinfo.admin.tabs' => 'tabs',
                    'mb.core.featureinfo.admin.accordion' => 'accordion',
                ],
            ])
            ->add('autoActivate', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoActivate',
            ])
            ->add('printResult', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.printResult',
            ])
            ->add('deactivateOnClose', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.deactivateonclose',
            ])
            ->add('onlyValid', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.onlyvalid',
            ])
            ->add('width', IntegerType::class, [
                'required' => true,
                'label' => 'mb.core.featureinfo.admin.width',
            ])
            ->add('height', IntegerType::class, [
                'required' => true,
                'label' => 'mb.core.featureinfo.admin.height',
            ])
            ->add('maxCount', IntegerType::class, [
                'required' => false,
                'label' => 'mb.core.featureinfo.admin.maxcount',
                'attr' => [
                    'placeholder' => 100,
                ],
            ])
            ->add('highlighting', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.highlighting',
            ])
            ->add('defaultStyle', PaintType::class, [
                'label' => 'mb.core.admin.featureinfo.label.default_group',
                'inherit_data' => true,
                'hasFont' => true,
                'hasPointRadius' => true,
                'fieldNameFillColor' => 'fillColorDefault',
                'fieldNameStrokeColor' => 'strokeColorDefault',
                'fieldNameStrokeWidth' => 'strokeWidthDefault',
                'fieldNameFontColor' => 'fontColorDefault',
                'fieldNameFontSize' => 'fontSizeDefault',
                'fieldNamePointRadius' => 'pointRadiusDefault',
            ])
            ->add('hoverStyle', PaintType::class, [
                'label' => 'mb.core.admin.featureinfo.label.hover_group',
                'inherit_data' => true,
                'hasFont' => true,
                'hasPointRadius' => true,
                'fieldNameFillColor' => 'fillColorHover',
                'fieldNameStrokeColor' => 'strokeColorHover',
                'fieldNameStrokeWidth' => 'strokeWidthHover',
                'fieldNameFontColor' => 'fontColorHover',
                'fieldNameFontSize' => 'fontSizeHover',
                'fieldNamePointRadius' => 'pointRadiusHover',
            ])
        ;
    }
}
