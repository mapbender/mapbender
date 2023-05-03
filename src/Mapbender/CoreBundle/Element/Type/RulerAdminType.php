<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

class RulerAdminType extends AbstractType
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class,
                array(
                    'required' => true,
                    'label' => 'mb.core.ruler.admin.type',
                    'choices' => array(
                        "mb.core.ruler.tag.line" => "line",
                        "mb.core.ruler.tag.area" => "area",
                    ),
                ))
            ->add('help', TextType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.ruler.admin.help',
                'help' => 'mb.core.ruler.admin.help_help',
            ], $this->trans))
            ->add('strokeWidthWhileDrawing', IntegerType::class, array(
                'required' => false,
                'label' => 'mb.core.ruler.admin.stroke_width_while_drawing',
                'attr' => array(
                    'min' => 0,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
            ->add('style', PaintType::class, array(
                'label_attr' => ['style' => 'display: none'],
                'inherit_data' => true,
                'required' => false,
                'hasFont' => true,
                'fontColorHelp' => 'mb.core.ruler.admin.only_for_area',
                'fontSizeHelp' => 'mb.core.ruler.admin.only_for_area',
                'fillColorHelp' => 'mb.core.ruler.admin.only_for_area',
            ))
        ;;
    }

}
