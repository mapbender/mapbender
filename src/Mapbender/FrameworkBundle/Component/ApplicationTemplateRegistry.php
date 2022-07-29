<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;

class ApplicationTemplateRegistry
{
    /** @var IApplicationTemplateInterface[]|Template[] */
    protected $handlers = array();
    /** @var IApplicationTemplateInterface|Template */
    protected $fallback;

    /**
     * @param array<string, IApplicationTemplateInterface|string> $collection
     */
    public function __construct($collection, $fallback)
    {
        foreach ($collection as $handling => $item) {
            if (\is_object($item)) {
                $this->handlers[$handling] = $item;
            } else {
                $this->handlers[$handling] = new $item;
            }
        }
        if ($fallback) {
            if (!\is_object($fallback)) {
                $fallback = new $fallback;
            }
            $this->fallback = $fallback;
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
     * @return Template|null
     */
    public function getApplicationTemplate(Application $application)
    {
        $setting = $application->getTemplate();
        // Return null only for uninitialized application template prop
        // (may happen when submitting new application form)
        if ($setting) {
            if (!empty($this->handlers[$setting])) {
                return $this->handlers[$setting];
            } else {
                return $this->fallback;
            }
        } else {
            return null;
        }
    }
}
