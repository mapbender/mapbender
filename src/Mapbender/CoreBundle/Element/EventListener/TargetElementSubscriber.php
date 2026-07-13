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
    /** @var Collection|Element[] */
    protected $matchingElements;

    /**
     * @param Application $application
     * @param string $targetClassName
     */
    public function __construct(protected Application $application, protected $targetClassName)
    {
        $this->matchingElements = $this->application->getElements()->filter(function($element) use ($targetClassName): bool {
            /** @var Element $element */
            try {
                return is_a($element->getClass(), $targetClassName, true);
            } catch (\ErrorException) {
                // thrown by debug mode class loader on Symfony 3.4+
                return false;
            }
        });
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
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
