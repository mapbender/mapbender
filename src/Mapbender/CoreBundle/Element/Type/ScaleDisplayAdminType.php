<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScaleDisplayAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $trans)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scalePrefix', TextType::class, $this->createInlineHelpText([
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.scaledisplay.scale_prefix',
                'help' => 'mb.core.scaledisplay.scale_prefix.help',
            ], $this->trans))
            ->add('unitPrefix', CheckboxType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.scaledisplay.unit_prefix',
                'help' => 'mb.core.scaledisplay.unit_prefix.help',
            ], $this->trans))
        ;
    }

}
