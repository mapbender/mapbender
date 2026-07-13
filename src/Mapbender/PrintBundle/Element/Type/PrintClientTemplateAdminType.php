<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PrintClientTemplateAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('template', TextType::class, [
                'required' => false,
                'label' => 'mb.core.printclienttemplate.admin.template',
            ])
            ->add('label', TextType::class, [
                'required' => false,
                'label' => 'mb.core.printclienttemplate.admin.label',
            ])
        ;
    }

}
