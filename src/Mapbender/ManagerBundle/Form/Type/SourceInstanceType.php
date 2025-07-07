<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Entity\SupportsOpacity;
use Mapbender\CoreBundle\Entity\SupportsProxy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class SourceInstanceType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'source_instance';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $class = $options['data_class'];
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'mb.manager.source.option.title',
            ])
            ->add('basesource', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.basesource',
            ])
        ;

        if (is_subclass_of($class, SupportsProxy::class)) {
            $builder->add('proxy', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.proxy',
            ]);
        }

        if (is_subclass_of($class, SupportsOpacity::class)) {
            $builder->add('opacity', IntegerType::class, [
                'label' => 'mb.wms.wmsloader.repo.instance.label.opacity',
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                ],
                'constraints' => [
                    new Constraints\Range([
                        'min' => 0,
                        'max' => 100,
                    ]),
                ],
                'required' => false,
            ]);
        }
    }
}
