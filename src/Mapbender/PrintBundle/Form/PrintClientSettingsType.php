<?php


namespace Mapbender\PrintBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;
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
        $baseChoiceOptions = Kernel::MAJOR_VERSION >= 3 ? array() : array(
            'choices_as_values' => true,
        );
        $templateChoices = array();
        foreach ($options['templates'] as $templateOption) {
            $templateChoices[$templateOption['label']] = $templateOption['template'];
        }
        $qualityChoices = array();
        foreach ($options['quality_levels'] as $qualityOption) {
            $qualityChoices[$qualityOption['label']] = $qualityOption['dpi'];
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
            ->add('template', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $baseChoiceOptions + array(
                'choices' => $templateChoices,
                'label' => 'mb.core.printclient.label.template',
            ))
            ->add('quality', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $baseChoiceOptions + array(
                'choices' => $qualityChoices,
                'label' => 'mb.core.printclient.label.quality',
            ))
            ->add('scale_select', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $baseChoiceOptions + array(
                'choices' => $scaleChoices,
                'label' => 'mb.core.printclient.label.scale',
            ))
            ->add('rotation', $rotationType, array(
                'label' => 'mb.core.printclient.label.rotation',
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
    }
}
