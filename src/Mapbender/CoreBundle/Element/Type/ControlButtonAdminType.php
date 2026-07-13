<?php


namespace Mapbender\CoreBundle\Element\Type;


use Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ControlButtonAdminType extends BaseButtonAdminType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('target', ControlTargetType::class, [
                'constraints' => [new NotBlank()],
                'placeholder' => 'mb.form.choice_required',
                'label' => 'mb.core.controlbutton.admin.target',
            ])
            ->add('group', TextType::class, [
                'required' => false,
                'label' => 'mb.core.controlbutton.admin.group',
            ])
        ;
        parent::buildForm($builder, $options);
    }
}
