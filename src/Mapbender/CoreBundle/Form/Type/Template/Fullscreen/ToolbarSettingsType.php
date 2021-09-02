<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarSettingsType extends BaseToolbarType implements EventSubscriberInterface
{
    protected $allowResponsiveContainers;

    public function __construct($allowResponsiveContainers)
    {
        $this->allowResponsiveContainers = $allowResponsiveContainers;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
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
        $builder->addEventSubscriber($this);
        parent::buildForm($builder, $options);
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        if ($event->getData()) {
            /** @var RegionProperties $rp */
            $rp = $event->getData();
            if ($this->allowResponsiveContainers && $rp->getApplication()->getMapEngineCode() === Application::MAP_ENGINE_OL2) {
                $event->getForm()->remove('screenType');
            }
        }
    }
}
