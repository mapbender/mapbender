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

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmsinstancelayer';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new FieldSubscriber();
        $builder->addEventSubscriber($subscriber);
        $builder
            ->add('title', 'text', array(
                    'required' => false,
            ))
            ->add('active', 'checkbox', array(
                'required' => false,
            ))
            ->add('selected', 'checkbox',
                  array(
                'required' => false))
            ->add('info', 'checkbox',
                  array(
                'required' => false,
                'disabled' => true))
            ->add('toggle', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowselected', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
                'disabled' => true,
            ))
            ->add('allowtoggle', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowreorder', 'checkbox', array(
                'required' => false,
            ))
            ->add('minScale', 'text',
                  array(
                'required' => false))
            ->add('maxScale', 'text', array(
                'required' => false,
            ))
            ->add('priority', 'hidden', array(
                'required' => true,
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var WmsInstanceLayer $layer */
        $layer = $form->getData();
        $hasSubLayers = !!$layer->getSublayer()->count();

        $view['title']->vars['attr'] = array(
            'placeholder' => $layer->getSourceItem()->getTitle(),
        );
        $view['toggle']->vars['disabled'] = !$hasSubLayers;
        $view['allowtoggle']->vars['disabled'] = !$hasSubLayers;
        if (!$hasSubLayers) {
            $form['toggle']->setData(false);
            $form['allowtoggle']->setData(false);
        }

        $isQueryable = $layer->getSourceItem()->getQueryable();
        $view['info']->vars['disabled'] = !$isQueryable;
        $view['allowinfo']->vars['disabled'] = !$isQueryable;
        if (!$isQueryable) {
            $form['info']->setData(false);
            $form['allowinfo']->setData(false);
        }
        parent::finishView($view, $form, $options);
    }
}
