<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @author Paul Schmidt
 */
class WmtsInstanceLayerType extends AbstractType
    implements EventSubscriberInterface
{

    public function getParent()
    {
        return TileInstanceLayerType::class;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
        $builder
            ->add('info', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.infotoc',
            ))
            ->add('allowinfo', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.allowinfotoc',
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // NOTE: collection prototype view does not have data
        /** @var WmtsInstanceLayer|null $layer */
        $layer = $form->getData();
        if ($layer) {
            $isQueryable = !!$layer->getSourceItem()->getInfoformats();
        } else {
            $isQueryable = false;
        }
        $view['allowinfo']->vars['disabled'] = !$isQueryable;
        $view['allowinfo']->vars['columnClass'] = 'group-start';
        $view['info']->vars['disabled'] = !$isQueryable;
        $view['info']->vars['columnClass'] = 'group-end';
        if (!$isQueryable) {
            $form['info']->setData(false);
            $form['allowinfo']->setData(false);
        }
        $view['info']->vars['checkbox_group'] = 'checkInfoOn';
        $view['allowinfo']->vars['checkbox_group'] = 'checkInfoAllow';
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        if ($event->getData()) {
            $this->reconfigureFields($event->getForm(), $event->getData());
        }
    }

    protected function reconfigureFields(FormInterface $form, WmtsInstanceLayer $data)
    {
        $choices = array();
        foreach ($data->getSourceItem()->getStyles() as $style) {
            $label = $style->getTitle() ?: $style->getIdentifier();
            $choices[$label] = $style->getIdentifier();
        }
        $form->add('style', ChoiceType::class, array(
            'label' => 'mb.wmts.wmtsloader.repo.instance.label.style',
            'choices' => $choices,
            "required" => false,
            'placeholder' => false,
        ));
    }
}
