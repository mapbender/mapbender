<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ControlButtonAdminType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType', array(
                'constraints' => array(new NotBlank()),
            ))
            ->add('group', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
        ;
    }
}
