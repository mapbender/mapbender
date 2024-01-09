<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class ActivityIndicatorAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.activityindicator.admin.tooltip',
            ))
            ->add('activityClass', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.activityindicator.admin.activityclass',
            ))
            ->add('ajaxActivityClass', TextType::class, $this->createInlineHelpText([
                'required' => false,
                'help' => 'mb.core.activityindicator.admin.ajaxactivityclass_help',
                'label' => 'mb.core.activityindicator.admin.ajaxactivityclass',
            ], $this->translator))
            ->add('tileActivityClass', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.activityindicator.admin.tileactivityclass',
            ))
        ;
    }

}
