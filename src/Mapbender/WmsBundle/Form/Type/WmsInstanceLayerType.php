<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Form\EventListener\FieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class WmsInstanceLayerType extends AbstractType
{

    use MapbenderTypeTrait;

    public function __construct(
        protected TranslatorInterface $translator,
    )
    {
    }

    public function getParent(): string
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $subscriber = new FieldSubscriber();
        $builder->addEventSubscriber($subscriber);
        $builder
            ->add('info', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.infotoc',
            ))
            ->add('toggle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.toggletoc',
            ))
            ->add('allowinfo', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowinfotoc',
            ))
            ->add('allowtoggle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowtoggletoc',
            ))
            ->add('minScale', TextType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.minscale',
                'attr' => ['class' => 'minScale'],
            ))
            ->add('maxScale', TextType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.maxscale',   // sic!
                'attr' => ['class' => 'maxScale'],
            ))
            ->add('priority', HiddenType::class, array(
                'required' => true,
            ))
            ->add('legend', CheckboxType::class, $this->createInlineHelpText(array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.legend',
                'help' => 'mb.manager.source.instancelayer.legend_help',
            ), $this->translator))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // NOTE: collection prototype view does not have data
        /** @var WmsInstanceLayer|null $layer */
        $layer = $form->getData();
        $hasSubLayers = $layer && $layer->getSublayer()->count();

        $view['legend']->vars['checkbox_group'] = 'legend';
        $view['legend']->vars['disabled'] = $hasSubLayers;

        $view['toggle']->vars['disabled'] = !$hasSubLayers;
        $view['allowtoggle']->vars['disabled'] = !$hasSubLayers;
        if (!$hasSubLayers && !$form->isSubmitted()) {
            $form['toggle']->setData(false);
            $form['allowtoggle']->setData(false);
        }

        if ($layer && $layer->getSourceItem()) {
            $isQueryable = $layer->getSourceItem()->getQueryable();
        } else {
            $isQueryable = false;
        }
        $view['info']->vars['disabled'] = !$isQueryable;
        $view['allowinfo']->vars['disabled'] = !$isQueryable;
        if (!$isQueryable && !$form->isSubmitted()) {
            $form['info']->setData(false);
            $form['allowinfo']->setData(false);
        }
        if ($layer && $layer->getSourceItem()) {
            $view['minScale']->vars['attr']['placeholder'] = $layer->getInheritedMinScale();
            $view['maxScale']->vars['attr']['placeholder'] = $layer->getInheritedMaxScale();
            $view['displayName']->vars['value'] = $layer->getSourceItem()->getName();
        }
        $view['allowinfo']->vars['checkbox_group'] = 'checkInfoAllow';
        $view['allowinfo']->vars['columnClass'] = 'group-start';
        $view['info']->vars['checkbox_group'] = 'checkInfoOn';
        $view['info']->vars['columnClass'] = 'group-end';
        $view['allowtoggle']->vars['checkbox_group'] = 'checkToggleAllow';
        $view['allowtoggle']->vars['columnClass'] = 'group-start';
        $view['toggle']->vars['checkbox_group'] = 'checkToggleOn';
        $view['toggle']->vars['columnClass'] = 'group-end';
    }
}
