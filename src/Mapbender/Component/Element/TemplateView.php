<?php


namespace Mapbender\Component\Element;


class TemplateView extends ElementView
{
    /** @var mixed[] */
    public $variables = array();
    /** @var string */
    protected $template;

    public function __construct($template)
    {
        $this->setTemplate($template);
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
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
