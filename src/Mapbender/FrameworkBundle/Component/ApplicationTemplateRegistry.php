<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;

class ApplicationTemplateRegistry
{
    /** @var IApplicationTemplateInterface[]|Template[] */
    protected $handlers = array();

    /**
     * @param array<string, IApplicationTemplateInterface|string> $collection
     */
    public function __construct($collection)
    {
        foreach ($collection as $handling => $item) {
            if (\is_object($item)) {
                $this->handlers[$handling] = $item;
            } else {
                $this->handlers[$handling] = new $item;
            }
        }
    }

    /**
     * @return IApplicationTemplateInterface[]|Template[]
     */
    public function getAll()
    {
        $noDuplicates = array();
        foreach ($this->handlers as $handler) {
            $noDuplicates += array(\get_class($handler) => $handler);
        }
        return \array_values($noDuplicates);
    }

    /**
     * @param Application $application
     * @return Template
     */
    public function getApplicationTemplate(Application $application)
    {
        return $this->handlers[$application->getTemplate()];
    }
}
