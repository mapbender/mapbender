<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ControlButtonAdminType extends BaseButtonAdminType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType', array(
                'constraints' => array(new NotBlank()),
                'placeholder' => 'mb.form.choice_required',
            ))
            ->add('group', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
        ;
        parent::buildForm($builder, $options);
    }
}
