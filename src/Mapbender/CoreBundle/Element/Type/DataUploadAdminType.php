<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataUploadAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $trans)
    {
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('maxFileSize', IntegerType::class, [
                'required' => true,
                'label' => 'mb.core.dataupload.admin.maxFileSize',
            ])
            ->add('helpText', TextareaType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.core.dataupload.admin.helpLabel',
                'help' => 'mb.core.dataupload.admin.helpInfo',
            ], $this->trans))
        ;
    }
}
