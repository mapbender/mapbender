<?php

namespace Mapbender\CoreBundle\Extension\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension that provides a filter to convert URLs in text to clickable HTML links.
 *
 * Basic usage - opens links in new tab
 * {{ myVariable|linkify }}
 *
 * Open links in same window
 * {{ myVariable|linkify('_self') }}
 *
 * With custom CSS class
 * {{ myVariable|linkify('_blank', 'external-link') }}
 *
 * No target attribute
 * {{ myVariable|linkify('') }}
 */
class LinkifyTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'mbcore_linkify';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('linkify', [$this, 'linkify'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Finds URLs in text and wraps them in anchor tags.
     *
     * @param string|null $text The input text that may contain URLs
     * @param string $target The target attribute for links (default: '_blank')
     * @param string|null $class Optional CSS class to add to the links
     * @return string The text with URLs converted to clickable links
     */
    public function linkify(?string $text, string $target = '_blank', ?string $class = null): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Escape HTML entities first to prevent XSS
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Regular expression to match URLs (http, https, ftp)
        // Matches URLs with optional query parameters and fragments
        $pattern = '/\b((?:https?|ftp):\/\/[^\s<>\[\]"\'\)]+)/i';

        // Build the replacement with optional attributes
        $attributes = '';
        if ($target) {
            $attributes .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($class) {
            $attributes .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
        }

        // Replace URLs with anchor tags
        $result = preg_replace_callback($pattern, function ($matches) use ($attributes) {
            $url = $matches[1];
            // Remove trailing punctuation that's likely not part of the URL
            $trailingPunctuation = '';
            if (preg_match('/([.,;:!?\)]+)$/', $url, $punctMatches)) {
                $trailingPunctuation = $punctMatches[1];
                $url = substr($url, 0, -strlen($trailingPunctuation));
            }
            return '<a href="' . $url . '"' . $attributes . '>' . $url . '</a>' . $trailingPunctuation;
        }, $text);

        return $result;
    }
}
