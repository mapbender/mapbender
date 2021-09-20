<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ScaleBarAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // @todo: add missing field labels
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
            // @todo: should be an optional positive integer
            ->add('maxWidth', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            ->add('units', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    'kilometer' => 'km',
                    'mile' => 'ml',
                ),
            ))
        ;
    }

}
