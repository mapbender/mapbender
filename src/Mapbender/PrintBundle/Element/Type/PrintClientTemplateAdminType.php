<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PrintClientTemplateAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('template', 'text', array('required' => false))
            ->add('label', 'text', array('required' => false))
        ;
    }

}