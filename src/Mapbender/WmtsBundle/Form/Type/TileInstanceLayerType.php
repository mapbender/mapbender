<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class TileInstanceLayerType extends AbstractType
{
    public function getParent()
    {
        return SourceInstanceItemType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('supportedCrs', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'mb.wmts.wmtsloader.repo.tilematrixset.label.supportedcrs',
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var WmtsInstanceLayer|null $instanceLayer */
        $instanceLayer = $form->getData();
        if ($instanceLayer) {
            $view['displayName']->vars['value'] = $instanceLayer->getSourceItem()->getIdentifier();
            $view['supportedCrs']->vars['value'] = \implode(', ', $instanceLayer->getSourceItem()->getSupportedCrsNames());
        }
    }
}
