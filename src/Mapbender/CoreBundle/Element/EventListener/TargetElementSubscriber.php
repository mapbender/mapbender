<?php


namespace Mapbender\CoreBundle\Element\EventListener;


use Doctrine\Common\Collections\Collection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TargetElementSubscriber implements EventSubscriberInterface
{
    /** @var string */
    protected $targetClassName;
    /** @var Application */
    protected $application;
    /** @var Collection|Element[] */
    protected $matchingElements;

    /**
     * @param Application $application
     * @param string $targetClassName
     */
    public function __construct(Application $application, $targetClassName)
    {
        $this->targetClassName = $targetClassName;
        $this->application = $application;
        $this->matchingElements = $this->application->getElements()->filter(function($element) use ($targetClassName) {
            /** @var Element $element */
            try {
                return is_a($element->getClass(), $targetClassName, true);
            } catch (\ErrorException $e) {
                // thrown by debug mode class loader on Symfony 3.4+
                return false;
            }
        });
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        if (!$event->getData()) {
            if ($this->matchingElements->count()) {
                /** @var Element $initialTarget */
                $initialTarget = $this->matchingElements->first();
                $event->setData($initialTarget->getId());
            }
        }
    }

    /**
     * @return Collection|Element[]
     */
    public function getMatchingElements()
    {
        return $this->matchingElements;
    }
}
