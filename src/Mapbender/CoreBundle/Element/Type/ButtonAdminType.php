<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ButtonAdminType extends AbstractType
{
    public function getParent(): string
    {
        return 'Mapbender\CoreBundle\Element\Type\ControlButtonAdminType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('click', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.button.admin.click',
            ))
            ->add('action', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.button.admin.action',
            ))
            ->add('deactivate', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.button.admin.deactivate',
            ))
        ;
    }

}
