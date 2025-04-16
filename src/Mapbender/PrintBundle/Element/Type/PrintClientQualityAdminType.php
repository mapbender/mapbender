<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PrintClientQualityAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dpi', NumberType::class, array(
                'required' => false,
            ))
            ->add('label', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.printclientquality.admin.label',
            ))
        ;
    }

}
