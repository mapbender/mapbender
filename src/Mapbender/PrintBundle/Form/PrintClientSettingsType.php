<?php


namespace Mapbender\PrintBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrintClientSettingsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required_fields_first' => false,
            'custom_fields' => array(),
            'templates' => array(),
            'quality_levels' => array(),
            'scales' => array(),
            'show_rotation' => true,
            'show_printLegend' => true,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $templateChoices = array();
        foreach ($options['templates'] as $templateOption) {
            $templateChoices[$templateOption['label']] = $templateOption['template'];
        }
        $qualityChoices = array();
        foreach ($options['quality_levels'] as $qualityOption) {
            if (!empty($qualityOption['dpi'])) {
                $qualityChoices[$qualityOption['label']] = $qualityOption['dpi'];
            }
        }
        $scaleChoices = array();
        foreach ($options['scales'] as $scale) {
            $scaleChoices["1:{$scale}"] = $scale;
        }
        if ($options['show_rotation']) {
            $rotationType = 'Symfony\Component\Form\Extension\Core\Type\TextType';
        } else {
            $rotationType = 'Symfony\Component\Form\Extension\Core\Type\HiddenType';
        }
        $builder
            ->add('custom_top', 'Symfony\Component\Form\Extension\Core\Type\FormType', array(
                'compound' => true,
                'inherit_data' => true,
                'mapped' => false,
                'property_path' => 'extra',
            ))
            ->add('template', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $templateChoices,
                'label' => 'mb.core.printclient.label.template',
            ))
        ;
        if (count($qualityChoices) > 1) {
            $builder->add('quality', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $qualityChoices,
                'label' => 'mb.core.printclient.label.quality',
            ));
        } else {
            $dpis = array_values($qualityChoices);
            $builder->add('quality', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
                'data' => $dpis ? $dpis[0] : '72',
            ));
        }
        $builder
            ->add('scale_select', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $scaleChoices,
                'label' => 'mb.core.printclient.label.scale',
            ))
            ->add('rotation', $rotationType, array(
                'label' => 'mb.core.printclient.label.rotation',
            ))
            ->add('custom_bottom', 'Symfony\Component\Form\Extension\Core\Type\FormType', array(
                'compound' => true,
                'inherit_data' => true,
                'mapped' => false,
                'property_path' => 'extra',
            ))
        ;
        if ($options['show_printLegend']) {
            $builder
                ->add('printLegend', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                    'label' => 'mb.core.printclient.label.legend',
                    'required' => false,
                ))
            ;
        }
        foreach ($options['custom_fields'] as $key => $fieldConfig) {
            $isRequired = !empty($fieldConfig['options']['required']);
            if ($options['required_fields_first'] && $isRequired) {
                $targetName = 'custom_top';
            } else {
                $targetName = 'custom_bottom';
            }
            $fieldName = 'extra_' . $key . '';
            $builder->get($targetName)->add($fieldName, 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => $isRequired,
                'mapped' => false,
                'inherit_data' => false,
                'data' => '',
                'label' => $fieldConfig['label'],
            ));
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Mangle input names (~submit property paths) of custom fields to keep data format compatible with print
        // backend / frontend / stored jobs
        foreach ($view['custom_bottom']->children as $k => $child) {
            $child->vars['full_name'] = 'extra[' . preg_replace('#^extra_#', '', $k) . ']';
        }
        foreach ($view['custom_top']->children as $k => $child) {
            $child->vars['full_name'] = 'extra[' . preg_replace('#^extra_#', '', $k) . ']';
        }
    }
}
