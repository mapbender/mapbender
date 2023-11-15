<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentSettingsType extends BaseToolbarType
{
    use MapbenderTypeTrait;

    protected TranslatorInterface $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'splashscreen' => true,
            'autohide_splashscreen' => true,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('splashscreen', CheckboxType::class, $this->createInlineHelpText([
            'label' => 'Show splashscreen',
            'help' => "If true, while an application is loading, a splashscreen is shown using the branding logo and the application's title and description"
        ], $this->trans))->add('autohide_splashscreen', CheckboxType::class, $this->createInlineHelpText([
            'label' => 'Auto-hide splashscreen',
            'help' => "If true, the splashscreen will be automatically dismissed once the application is ready for user interaction. Otherwise, the user needs to click once to dismiss the splashscreen."
        ], $this->trans));
    }
}
