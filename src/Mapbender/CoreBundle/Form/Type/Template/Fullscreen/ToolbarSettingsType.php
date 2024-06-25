<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarSettingsType extends BaseToolbarType
{
    protected $allowResponsiveContainers;

    public function __construct($allowResponsiveContainers)
    {
        $this->allowResponsiveContainers = $allowResponsiveContainers;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'compound' => true,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->allowResponsiveContainers) {
            $builder->add('screenType', 'Mapbender\ManagerBundle\Form\Type\ScreentypeType', array(
                'label' => 'mb.manager.screentype.label',
            ));
        }
        parent::buildForm($builder, $options);
    }
}
