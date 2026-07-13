<?php


namespace Mapbender\Component\Element;


class TemplateView extends ElementView
{
    /** @var mixed[] */
    public $variables = [];
    /** @var string */
    protected $template;

    public function __construct($template)
    {
        $this->setTemplate($template);
    }

    /**
     * @param string $template
     */
    public function setTemplate($template): void
    {
        if (!$template) {
            throw new \InvalidArgumentException("Template cannot be empty");
        }
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
