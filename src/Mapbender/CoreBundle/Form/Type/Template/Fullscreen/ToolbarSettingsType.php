<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Mapbender\ManagerBundle\Form\Type\ScreentypeType;
use Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarSettingsType extends BaseToolbarType
{
    public function __construct(protected $allowResponsiveContainers)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'compound' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->allowResponsiveContainers) {
            $builder->add('screenType', ScreentypeType::class, [
                'label' => 'mb.manager.screentype.label',
            ]);
        }
        parent::buildForm($builder, $options);
    }
}
