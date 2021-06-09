<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RulerAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
            ->add('type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                array(
                'required' => true,
                'choices' => array(
                    "line" => "line",
                    "area" => "area",
                ),
                'choices_as_values' => true,
            ))
        ;
    }

}
