<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\WmsBundle\Form\EventListener\FieldSubscriber;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class WmsInstanceLayerType extends AbstractType
{

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
            ->add('info', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.infotoc',
            ))
            ->add('toggle', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.toggletoc',
            ))
            ->add('allowinfo', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowinfotoc',
            ))
            ->add('allowtoggle', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowtoggletoc',
            ))
            ->add('minScale', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.minscale',
                'attr' => ['class' => 'minScale'],
            ))
            ->add('maxScale', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.maxscale',   // sic!
                'attr' => ['class' => 'maxScale'],
            ))
            ->add('priority', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
                'required' => true,
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // NOTE: collection prototype view does not have data
        /** @var WmsInstanceLayer|null $layer */
        $layer = $form->getData();
        $hasSubLayers = $layer && $layer->getSublayer()->count();

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
