<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Mapbender\CoreBundle\Entity\Style;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StyleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'mb.ogcapifeatures.admin.style.editor.name_label',
                'attr' => [
                    'placeholder' => 'mb.ogcapifeatures.admin.style.editor.name_placeholder',
                ],
                'constraints' => [
                    new NotBlank(message: 'mb.ogcapifeatures.admin.style.name_required'),
                ],
            ])
            ->add('style', TextareaType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.style.editor.mapbox_style_json',
                'attr' => [
                    'rows' => 24,
                    'id' => 'style',
                ],
            ])
            ->add('sourceType', HiddenType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Style::class,
        ]);
    }
}
