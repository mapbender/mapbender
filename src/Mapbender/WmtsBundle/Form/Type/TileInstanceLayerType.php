<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class TileInstanceLayerType extends AbstractType
{
    public function getParent(): string
    {
        return SourceInstanceItemType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supportedCrs', TextType::class, array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'mb.wmts.wmtsloader.repo.tilematrixset.label.supportedcrs',
            ))
            ->add('toggle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.toggletoc',
            ))
            ->add('allowtoggle', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowtoggletoc',
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var WmtsInstanceLayer|null $layer */
        $layer = $form->getData();
        if ($layer) {
            $view['displayName']->vars['value'] = $layer->getSourceItem()->getIdentifier();
            $view['supportedCrs']->vars['value'] = \implode(', ', $layer->getSourceItem()->getSupportedCrsNames());
        }
        $view['toggle']->vars['disabled'] = $layer?->getParent() !== null;
        $view['toggle']->vars['checkbox_group'] = 'checkToggleOn';
        $view['toggle']->vars['columnClass'] = 'group-end';
        $view['allowtoggle']->vars['disabled'] = $layer?->getParent() !== null;
        $view['allowtoggle']->vars['checkbox_group'] = 'checkToggleAllow';
        $view['allowtoggle']->vars['columnClass'] = 'group-start';
    }
}
