<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarSettingsType extends AbstractType
{
    protected $allowResponsiveContainers;

    public function __construct($allowResponsiveContainers)
    {
        $this->allowResponsiveContainers = $allowResponsiveContainers;
    }

    public function getParent()
    {
        return 'Mapbender\CoreBundle\Form\Type\Template\RegionSettingsType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => true,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->allowResponsiveContainers) {
            $builder->add('screenType', 'Mapbender\ManagerBundle\Form\Type\ScreentypeType', array(
                'label' => 'mb.manager.screentype.label',
            ));
        }
    }
}
