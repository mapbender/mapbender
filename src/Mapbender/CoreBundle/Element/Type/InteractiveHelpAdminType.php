<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InteractiveHelpAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tour', YAMLConfigurationType::class, $this->createInlineHelpText([
                'label' => 'mb.interactivehelp.admin.config',
                'required' => false,
                'attr' => [
                    'class' => 'code-yaml',
                ],
                'label_attr' => [
                    'class' => 'block',
                ],
                'help' => 'mb.interactivehelp.admin.help',
            ], $this->trans))
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.interactivehelp.admin.showOnStart',
            ))
        ;
    }
}
