<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\WmtsBundle\Form\EventListener\FieldSubscriber;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @author Paul Schmidt
 */
class WmtsInstanceLayerType extends AbstractType
{

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
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.layerstitle',
            ))
            ->add('active', 'checkbox', array(
                'required' => false
            ))
            ->add('selected', 'checkbox', array(
                'required' => false
            ))
            ->add('info', 'checkbox', array(
                'required' => false,
            ))
            ->add('toggle', 'checkbox', array(
                'disabled' => true,
                'auto_initialize' => false,
            ))
            ->add('allowselected', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowtoggle', 'checkbox', array(
                'disabled' => true,
                'auto_initialize' => false,
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var WmtsInstanceLayer $layer */
        $layer = $form->getData();

        $view['title']->vars['attr'] = array(
            'placeholder' => $layer->getSourceItem()->getTitle(),
        );
        $isQueryable = !!$layer->getSourceItem()->getInfoformats();
        $view['info']->vars['disabled'] = !$isQueryable;
        $view['allowinfo']->vars['disabled'] = !$isQueryable;
        if (!$isQueryable) {
            $form['info']->setData(false);
            $form['allowinfo']->setData(false);
        }
        parent::finishView($view, $form, $options);
    }

}
