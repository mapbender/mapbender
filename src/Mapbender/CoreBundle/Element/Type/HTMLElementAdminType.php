<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HTMLElementAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', 'Mapbender\CoreBundle\Form\Type\HtmlFormType', [
                'required' => false,
            ])
            ->add('classes', 'Symfony\Component\Form\Extension\Core\Type\TextType', [
                'required' => false,
            ])
        ;
    }
}
