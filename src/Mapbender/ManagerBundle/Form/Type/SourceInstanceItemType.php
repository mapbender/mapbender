<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SourceInstanceItemType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'source_instance_item';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.layerstitle',
            ))
            ->add('active', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.active',
            ))
            ->add('allowselected', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => "mb.wms.wmsloader.repo.instancelayerform.label.allowselecttoc",
            ))
            ->add('selected', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.selectedtoc',
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var SourceInstanceItem $layer */
        $layer = $form->getData();

        $view['title']->vars['attr'] = array(
            'placeholder' => $layer->getSourceItem()->getTitle(),
        );
    }
}
