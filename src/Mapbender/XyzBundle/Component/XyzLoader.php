<?php

namespace Mapbender\XyzBundle\Component;

use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\XyzBundle\Entity\XyzSource;
use Mapbender\XyzBundle\Type\XyzSourceType;

class XyzLoader extends SourceLoader
{

    public function loadSource(mixed $formData): Source
    {
        $source = new XyzSource();
        $this->refreshSource($source, $formData);
        return $source;
    }

    public function refreshSource(Source $source, mixed $formData): void
    {
        /** @var XyzSource $source */
        $urlTemplate = is_array($formData) ? $formData['urlTemplate'] : $formData->getUrlTemplate();
        $urlTemplate = $this->normalizeUrlTemplate(trim($urlTemplate));

        $source->setUrlTemplate($urlTemplate);
        $titleFromForm = is_array($formData) ? ($formData['title'] ?? '') : '';
        $source->setTitle(trim($titleFromForm) !== '' ? trim($titleFromForm) : $this->titleFromUrl($urlTemplate));
    }

    public function getFormType(): string
    {
        return XyzSourceType::class;
    }

    /**
     * Ensures the URL contains {z}/{x}/{y} placeholders.
     * If a bare base URL is given (e.g. "https://tile.openstreetmap.org/"),
     * the standard XYZ path "{z}/{x}/{y}.png" is appended automatically.
     */
    private function normalizeUrlTemplate(string $urlTemplate): string
    {
        if (empty($urlTemplate)) {
            throw new \InvalidArgumentException("URL template must not be empty");
        }
        // If the URL already contains all three placeholders, use it as-is
        if (preg_match('/\{z\}/', $urlTemplate) && preg_match('/\{x\}/', $urlTemplate)
            && preg_match('/\{-?y\}/', $urlTemplate)) {
            return $urlTemplate;
        }
        // Bare base URL: append standard XYZ tile path
        return rtrim($urlTemplate, '/') . '/{z}/{x}/{y}.png';
    }

    private function titleFromUrl(string $urlTemplate): string
    {
        $host = parse_url($urlTemplate, PHP_URL_HOST);
        return $host ? "XYZ Tiles ({$host})" : "XYZ Tiles";
    }
}
