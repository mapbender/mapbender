<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Style;

/**
 * Converts array-style style definitions loaded from YAML configuration
 * (parameter "styles", populated by MapbenderYamlCompilerPass) into
 * read-only {@see Style} entities.
 *
 * Service instance registered as mapbender.style.yaml_mapper.
 */
class StyleYamlMapper
{
    /** Source type marker for styles that originate from YAML configuration. */
    public const SOURCE_TYPE_YAML = 'yaml';

    /**
     * @param array<string, array> $definitions
     */
    public function __construct(protected array $definitions)
    {
    }

    /**
     * @return Style[]
     */
    public function getStyles(): array
    {
        $styles = array();
        foreach ($this->definitions as $key => $definition) {
            $styles[] = $this->createStyle((string) $key, $definition);
        }
        return $styles;
    }

    /**
     * Returns the YAML style for the given definition key, or null if none exists.
     */
    public function getStyle(string $key): ?Style
    {
        if (!array_key_exists($key, $this->definitions)) {
            return null;
        }
        return $this->createStyle($key, $this->definitions[$key]);
    }

    /**
     * @param array $definition
     */
    protected function createStyle(string $key, array $definition): Style
    {
        $style = new Style();
        $style->setYamlKey($key);
        $style->setName($definition['name'] ?? $key);
        $style->setSourceType(self::SOURCE_TYPE_YAML);
        $style->setStyle($this->normalizeStyleValue($definition['style'] ?? array()));
        if (isset($definition['collectionId'])) {
            $style->setCollectionId((string) $definition['collectionId']);
        }
        return $style;
    }

    /**
     * Accepts both a nested array (the natural YAML form) and a pre-encoded
     * JSON string, always returning a JSON string as stored on the entity.
     */
    protected function normalizeStyleValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }
}
