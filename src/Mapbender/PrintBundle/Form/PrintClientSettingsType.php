<?php


namespace Mapbender\PrintBundle\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrintClientSettingsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required_fields_first' => false,
            'custom_fields' => [],
            'templates' => [],
            'quality_levels' => [],
            'scales' => [],
            'show_rotation' => true,
            'show_printLegend' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $templateChoices = [];
        foreach ($options['templates'] as $templateOption) {
            $templateChoices[$templateOption['label']] = $templateOption['template'];
        }
        $qualityChoices = [];
        foreach ($options['quality_levels'] as $qualityOption) {
            if (!empty($qualityOption['dpi'])) {
                $qualityChoices[$qualityOption['label']] = $qualityOption['dpi'];
            }
        }
        $scaleChoices = [];
        foreach ($options['scales'] as $scale) {
            $scaleChoices["1:{$scale}"] = $scale;
        }
        if ($options['show_rotation']) {
            $rotationType = TextType::class;
        } else {
            $rotationType = HiddenType::class;
        }
        $builder
            ->add('custom_top', FormType::class, [
                'compound' => true,
                'inherit_data' => true,
                'mapped' => false,
                'property_path' => 'extra',
            ])
            ->add('template', ChoiceType::class, [
                'choices' => $templateChoices,
                'label' => 'mb.core.printclient.label.template',
            ])
        ;
        if (count($qualityChoices) > 1) {
            $builder->add('quality', ChoiceType::class, [
                'choices' => $qualityChoices,
                'label' => 'mb.core.printclient.label.quality',
            ]);
        } else {
            $dpis = array_values($qualityChoices);
            $builder->add('quality', HiddenType::class, [
                'data' => $dpis ? $dpis[0] : '72',
                'label' => 'mb.core.printclient.label.quality',
            ]);
        }
        $builder
            ->add('scale_select', ChoiceType::class, [
                'choices' => $scaleChoices,
                'label' => 'mb.core.printclient.label.scale',
            ])
            ->add('rotation', $rotationType, [
                'label' => 'mb.core.printclient.label.rotation',
            ])
            ->add('custom_bottom', FormType::class, [
                'compound' => true,
                'inherit_data' => true,
                'mapped' => false,
                'property_path' => 'extra',
            ])
        ;
        if ($options['show_printLegend']) {
            $builder
                ->add('printLegend', CheckboxType::class, [
                    'label' => 'mb.core.printclient.label.legend',
                    'required' => false,
                ])
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
            $builder->get($targetName)->add($fieldName, TextType::class, [
                'required' => $isRequired,
                'mapped' => false,
                'inherit_data' => false,
                'data' => '',
                'label' => $fieldConfig['label'],
            ]);
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Mangle input names (~submit property paths) of custom fields to keep data format compatible with print
        // backend / frontend / stored jobs
        foreach ($view['custom_bottom']->children as $k => $child) {
            $child->vars['full_name'] = 'extra[' . preg_replace('#^extra_#', '', (string) $k) . ']';
        }
        foreach ($view['custom_top']->children as $k => $child) {
            $child->vars['full_name'] = 'extra[' . preg_replace('#^extra_#', '', (string) $k) . ']';
        }
    }
}
