<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LinkButtonAdminType extends AbstractType
{

    public function getParent(): string
    {
        return BaseButtonAdminType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('click', TextType::class, [
                'required' => true,
                'label' => 'mb.core.linkbutton.admin.click',
            ])
            ->add('target', ChoiceType::class, [
                'required' => true,
                'label' => 'mb.core.linkbutton.admin.target.title',
                'choices' => [
                    'mb.core.linkbutton.admin.target.blank' => '_blank',
                    'mb.core.linkbutton.admin.target.self' => '_self',
                    'mb.core.linkbutton.admin.target.parent' => '_parent',
                    'mb.core.linkbutton.admin.target.top' => '_top',
                ]
            ])
        ;
    }
}
