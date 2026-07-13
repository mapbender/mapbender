<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionInstType extends AbstractType implements EventSubscriberInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['instance']);
        $resolver->setAllowedTypes('instance', [WmsInstance::class]);
        $resolver->setDefault('data_class', DimensionInst::class);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this);
        $builder
            ->add('active', CheckboxType::class, [
                'required' => true,
                'label' => 'active',
            ])
            ->add('name', TextType::class, [
                'auto_initialize' => false,
                'required' => true,
                'attr' => [
                    'readonly' => 'readonly',
                ],
            ])
            ->add('units', TextType::class, [
                'auto_initialize' => false,
                'required' => false,
                'attr' => [
                    'readonly' => 'readonly',
                ],
            ])
            ->add('unitSymbol', TextType::class, [
                'auto_initialize' => false,
                'required' => false,
                'attr' => [
                    'readonly' => 'readonly',
                ],
            ])
            ->add('multipleValues', CheckboxType::class, [
                'auto_initialize' => false,
                'label' => 'multiple',
                'attr' => [
                    'readonly' => 'readonly',
                ],
                'required' => false,
            ])
            ->add('nearestValue', CheckboxType::class, [
                'auto_initialize' => false,
                'label' => 'nearest',
                'attr' => [
                    'readonly' => 'readonly',
                ],
                'required' => false,
            ])
            ->add('current', CheckboxType::class, [
                'auto_initialize' => false,
                'label' => 'current',
                'attr' => [
                    'readonly' => 'readonly',
                ],
                'required' => false,
            ])
        ;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        $this->addFields($form, $data);
    }

    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    protected function addFields($form, $data)
    {
        $instance = $form->getConfig()->getOption('instance');
        $originalExtent = $this->getOriginalExtent($instance, $data->getName());
        $ranges = explode(',', (string) $originalExtent);

        $multipleRanges = count($ranges) > 1;
        if ($multipleRanges) {
            $extentType = HiddenType::class;
        } else {
            $extentType = TextType::class;
        }
        $form
            ->add('extent', $extentType, [
                'required' => true,
                'attr' => [
                    'readonly' => 'readonly',
                ],
                'label' => 'Extent',
            ])
        ;
        if ($multipleRanges) {
            $choices = array_combine($ranges, $ranges);
            $form->add('extentRanges', ChoiceType::class, [
                'data' => explode(',', (string) $data->getExtent()),
                'mapped' => false,
                'choices' => $choices,
                'label' => $form->get('extent')->getConfig()->getOption('label'),
                'auto_initialize' => false,
                'multiple' => true,
                'required' => true,
            ]);
        }

        $form->add('default', TextType::class, [
            'required' => false,
            'attr' => [
                'readonly' => 'readonly',
            ],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var DimensionInst $dimInst */
        $dimInst = $form->getData();
        $view->vars['diminstconfig'] = $dimInst->getConfiguration();
        /** @var WmsInstance $instance */
        $instance = $options['instance'];
        $view->vars['origextent'] = $this->getOriginalExtent($instance, $dimInst->getName());
    }

    protected function getOriginalExtent(WmsInstance $instance, $dimensionName)
    {
        foreach ($instance->getSource()->getDimensions() as $sourceDimension) {
            if ($sourceDimension->getName() === $dimensionName) {
                return $sourceDimension->getExtent();
            }
        }
        return null;
    }
}
