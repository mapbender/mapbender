<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

    public function getBlockPrefix(): string
    {
        return 'source_instance_item';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.title',
            ))
            ->add('allowselected', CheckboxType::class, array(
                'required' => false,
                'label' => "mb.manager.source.instancelayer.allowselecttoc",
            ))
            ->add('selected', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.selectedtoc',
            ))
            ->add('displayId', TextType::class, array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                    'title' => 'mb.manager.source.instancelayer.id.help',
                ),
                'label' => 'mb.manager.source.instancelayer.id',
            ))
            ->add('displayName', TextType::class, array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'mb.manager.source.instancelayer.name',
            ))
        ;
        $type = $this;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $e) use ($type) {
            $type->addActiveField($e->getForm(), $e->getData());
        });
    }

    protected function addActiveField(FormInterface $form, ?SourceInstanceItem $data)
    {
        $disabled = false;
        if ($data) {
            $instanceFactory = $this->typeDirectory->getInstanceFactory($data->getSourceInstance()->getSource());
            $disabled = !$instanceFactory->canDeactivateLayer($data);
        }
        if ($form->has('active')) {
            $form->remove('active');
        }
        $form->add('active', CheckboxType::class, array(
            'required' => false,
            'disabled' => $disabled,
            'label' => 'mb.manager.source.instancelayer.active',
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
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
