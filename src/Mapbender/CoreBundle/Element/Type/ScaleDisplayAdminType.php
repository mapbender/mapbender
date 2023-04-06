<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScaleDisplayAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scalePrefix', 'Symfony\Component\Form\Extension\Core\Type\TextType', $this->createInlineHelpText([
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.scaledisplay.scale_prefix',
                'help' => 'mb.core.scaledisplay.scale_prefix.help',
            ], $this->trans))
            ->add('unitPrefix', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.scaledisplay.unit_prefix',
                'help' => 'mb.core.scaledisplay.unit_prefix.help',
            ], $this->trans))
        ;
    }

}
