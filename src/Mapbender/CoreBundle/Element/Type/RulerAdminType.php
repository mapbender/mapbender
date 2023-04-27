<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
        ;
    }

}
