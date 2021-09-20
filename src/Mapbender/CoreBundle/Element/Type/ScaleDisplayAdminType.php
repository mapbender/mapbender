<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ScaleDisplayAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
            ->add('scalePrefix', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
            ))
            ->add('unitPrefix', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
        ;
    }

}
