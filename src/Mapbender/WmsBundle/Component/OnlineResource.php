<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class OnlineResource implements MutableUrlTarget
{
    /**
     *
     * @param string $format
     * @param string $href
     */
    public function __construct(public $format = null, public $href = null)
    {
    }

    /**
     * Set format
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set href
     *
     * @param string $href
     * @return $this
     */
    public function setHref($href): static
    {
        $this->href = $href;

        return $this;
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    public function mutateUrls(OneWayTransformer $transformer): void
    {
        $this->setHref($transformer->process($this->getHref()));
    }

    /**
     * Reconstruct from a plain array (e.g. after JSON column hydration by Doctrine/DBAL 4).
     *
     * @param array<string, mixed>|null $data
     * @return static|null
     */
    public static function fromArray(?array $data): ?static
    {
        if ($data === null) {
            return null;
        }
        return new static($data['format'] ?? null, $data['href'] ?? null);
    }
}
