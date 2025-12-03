<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;

class ApplicationSwitcherAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applications', YAMLConfigurationType::class, $this->createInlineHelpText([
                'label' => 'mb.datamanager.admin.schemes',
                'required' => false,
                'attr' => [
                    'class' => 'code-yaml',
                ],
                'label_attr' => [
                    'class' => 'block',
                ],
                'help' => 'mb.core.applicationSwitcher.admin.help',
            ], $this->trans))
            ->add('open_in_new_tab', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.applicationSwitcher.admin.open_in_new_tab',
            ])
        ;
    }
}
