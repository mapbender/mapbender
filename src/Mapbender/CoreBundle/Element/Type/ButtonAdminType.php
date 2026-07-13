<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ButtonAdminType extends AbstractType
{
    public function getParent(): string
    {
        return ControlButtonAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('click', TextType::class, [
                'required' => false,
                'label' => 'mb.core.button.admin.click',
            ])
            ->add('action', TextType::class, [
                'required' => false,
                'label' => 'mb.core.button.admin.action',
            ])
            ->add('deactivate', TextType::class, [
                'required' => false,
                'label' => 'mb.core.button.admin.deactivate',
            ])
        ;
    }

}
