<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SourceInstanceItemType extends AbstractType
{

    /** @var TypeDirectoryService */
    protected $typeDirectory;

    /**
     * SourceInstanceItemType constructor.
     * @param TypeDirectoryService $typeDirectory
     */
    public function __construct(TypeDirectoryService $typeDirectory)
    {
        $this->typeDirectory = $typeDirectory;
    }

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
            ->add('allowselected', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => "mb.wms.wmsloader.repo.instancelayerform.label.allowselecttoc",
            ))
            ->add('selected', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.selectedtoc',
            ))
            ->add('displayId', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                    'title' => 'mb.wms.wmsloader.repo.instancelayerform.label.id.description',
                ),
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.id.title',
            ))
            ->add('displayName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.layersname',
            ))
        ;
        $type = $this;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $e) use ($type) {
            $type->addActiveField($e->getForm(), $e->getData());
        });
    }

    /**
     * @param FormInterface $form
     * @param SourceInstanceItem|null $data
     */
    protected function addActiveField(FormInterface $form, $data)
    {
        $disabled = $data && !$this->typeDirectory->canDeactivateLayer($data);
        if ($form->has('active')) {
            $form->remove('active');
        }
        $form->add('active', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
            'required' => false,
            'disabled' => $disabled,
            'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.active',
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // NOTE: collection prototype view does not have data
        /** @var SourceInstanceItem|null $layer */
        $layer = $form->getData();

        if ($layer && $layer->getSourceItem()) {
            $view['title']->vars['attr'] += array(
                'placeholder' => $layer->getSourceItem()->getTitle(),
            );
        }
        $view['active']->vars['checkbox_group'] = 'checkActive';
        $view['allowselected']->vars['checkbox_group'] = 'checkSelectAllow';
        $view['allowselected']->vars['columnClass'] = 'group-start';
        $view['selected']->vars['checkbox_group'] = 'checkSelectOn';
        $view['selected']->vars['columnClass'] = 'group-end';
    }
}
