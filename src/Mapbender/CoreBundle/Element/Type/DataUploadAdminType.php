<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataUploadAdminType extends AbstractType
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('helpText', TextareaType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.dataupload.admin.helpLabel',
                'help' => 'mb.core.dataupload.admin.helpInfo',
            ], $this->trans))
        ;
    }
}
