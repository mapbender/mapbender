<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RulerAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $trans)
    {
    }


    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'mb.core.ruler.admin.type',
                    'choices' => [
                        "mb.core.ruler.tag.line" => "line",
                        "mb.core.ruler.tag.area" => "area",
                        "mb.core.ruler.tag.both" => "both",
                    ],
                ])
            ->add('help', TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.ruler.admin.help',
                'help' => 'mb.core.ruler.admin.help_help',
            ], $this->trans))
            ->add('strokeWidthWhileDrawing', IntegerType::class, [
                'required' => false,
                'label' => 'mb.core.ruler.admin.stroke_width_while_drawing',
                'attr' => [
                    'min' => 0,
                ],
                'constraints' => [
                    new Range(min: 0),
                ],
            ])
            ->add('style', PaintType::class, [
                'label' => 'mb.core.ruler.admin.style',
                'inherit_data' => true,
                'required' => false,
                'hasFont' => true,
                'fontColorHelp' => 'mb.core.ruler.admin.only_for_area',
                'fontSizeHelp' => 'mb.core.ruler.admin.only_for_area',
                'fillColorHelp' => 'mb.core.ruler.admin.only_for_area',
            ])
        ;;
    }

}
