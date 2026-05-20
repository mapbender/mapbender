<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class OnlineResource implements MutableUrlTarget
{
    /** @var string|null */
    public $format;

    /** @var string|null */
    public $href;

    /**
     *
     * @param string $format
     * @param string $href
     */
    public function __construct($format = null, $href = null)
    {
        $this->format = $format;
        $this->href   = $href;
    }

    /**
     * Set format
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
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
    public function setHref($href)
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

    public function mutateUrls(OneWayTransformer $transformer)
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
