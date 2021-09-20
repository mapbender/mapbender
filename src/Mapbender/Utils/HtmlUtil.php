<?php


namespace Mapbender\Utils;

/**
 * Renders HTML tags and can merge multi-value attributes.
 */
class HtmlUtil
{
    /**
     * Wraps $content into a tag with given $name and $attributes
     *
     * @param string $name
     * @param string $content
     * @param mixed[] $attributes
     * @return string
     */
    public static function renderTag($name, $content, $attributes)
    {
        return static::renderOpeningTag($name, $attributes) . $content . '</' . $name . '>';
    }

    /**
     * @param mixed[] $attributes
     * @return string
     * @throws \InvalidArgumentException when encountering unsafe attribute names
     */
    public static function renderAttributes($attributes)
    {
        $parts = array();
        foreach ($attributes as $name => $value) {
            if (!$name || \is_numeric($name) || !preg_match('#^[a-z][a-z\-\d]*$#i', $name)) {
                throw new \InvalidArgumentException("Unsafe HTML attribute name " . var_export($name, true));
            }
            // Boolean value handling:
            // Render (repeat of) attribute name instead of boolean true value
            // Render no attribute at all for boolean false value
            /** @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes#boolean_attributes */
            if ($value === true) {
                $value = $name;
            }
            if ($value !== false) {
                $parts[] = $name . '="' . \htmlspecialchars($value ?: '') . '"';
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Renders (only) the opening tag with given $name and $attributes
     *
     * @param $name
     * @param $attributes
     * @return string
     */
    public static function renderOpeningTag($name, $attributes)
    {
        return '<' . rtrim($name . ' ' . static::renderAttributes($attributes)) . '>';
    }

    /**
     * Merges $b onto $a. Concatenates 'class' and 'style' attributes if present in
     * both inputs.
     *
     * @param mixed[] $a
     * @param mixed[] $b
     * @return mixed[]
     */
    public static function mergeAttributes($a, $b)
    {
        $merged = array_replace($a, $b);
        if (\array_key_exists('class', $a) && \array_key_exists('class', $b)) {
            $merged['class'] = implode(' ', array_filter(array($a['class'], $b['class'])));
        }
        if (\array_key_exists('style', $a) && \array_key_exists('style', $b)) {
            $parts = array(
                rtrim($a['style'], "; \n"),
                rtrim($b['style'], "; \n"),
            );

            $merged['style'] = implode('; ', array_filter($parts)) . ';';
        }
        return $merged;
    }
}
