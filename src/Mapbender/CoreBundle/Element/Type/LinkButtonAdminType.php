<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LinkButtonAdminType extends AbstractType
{

    public function getParent()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('click', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.core.linkbutton.admin.click',
            ))
        ;
    }
}
