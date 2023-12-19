<?php


namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add the following snippet in the admin template when using this type:
 <script type="text/javascript">
    !(function($) {
        $('#{{ form.vars.attr.id }} .-js-init-colorpicker').colorpicker({format: 'rgba'});
    }(jQuery));
</script>
 */

class PaintType extends AbstractType
{
    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'hasStroke' => true,
                'hasFill' => true,
                'hasFont' => false,

                'fieldNameFillColor' => 'fillColor',
                'fieldNameStrokeColor' => 'strokeColor',
                'fieldNameStrokeWidth' => 'strokeWidth',
                'fieldNameFontColor' => 'fontColor',
                'fieldNameFontSize' => 'fontSize',
                'fillColorHelp' => null,
                'strokeColorHelp' => null,
                'strokeWidthHelp' => null,
                'fontColorHelp' => null,
                'fontSizeHelp' => null,
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['hasStroke']) {
            $builder->add($options['fieldNameStrokeColor'], TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.strokeColor',
                'attr' => ['class' => '-js-init-colorpicker'],
                'help' => $options['strokeColorHelp'],
            ], $this->trans));

            $builder->add($options['fieldNameStrokeWidth'], IntegerType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.stroke_width_px',
                'attr' => ['min' => 0],
                'help' => $options['strokeWidthHelp'],
                'constraints' => [new Constraints\Range(['min' => 0])],
            ], $this->trans));
        }

        if ($options['hasFill']) {
            $builder->add($options['fieldNameFillColor'], TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fillColor',
                'attr' => ['class' => '-js-init-colorpicker'],
                'help' => $options['fillColorHelp'],
            ], $this->trans));
        }

        if ($options['hasFont']) {
            $builder->add($options['fieldNameFontColor'], TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fontColor',
                'attr' => ['class' => '-js-init-colorpicker'],
                'help' => $options['fontColorHelp'],
            ], $this->trans));

            $builder->add($options['fieldNameFontSize'], IntegerType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.admin.featureinfo.label.fontSize',
                'attr' => ['min' => 1],
                'help' => $options['fontSizeHelp'],
                'constraints' => [new Constraints\Range(['min' => 1])],
            ], $this->trans));
        }

    }
}
