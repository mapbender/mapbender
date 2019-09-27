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

    public function getParent()
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new FieldSubscriber();
        $builder->addEventSubscriber($subscriber);
        $builder
            ->add('info', 'checkbox', array(
                'required' => false,
            ))
            ->add('toggle', 'checkbox', array(
                'disabled' => true,
                'auto_initialize' => false,
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

        $isQueryable = !!$layer->getSourceItem()->getInfoformats();
        $view['info']->vars['disabled'] = !$isQueryable;
        $view['allowinfo']->vars['disabled'] = !$isQueryable;
        if (!$isQueryable) {
            $form['info']->setData(false);
            $form['allowinfo']->setData(false);
        }
    }
}
