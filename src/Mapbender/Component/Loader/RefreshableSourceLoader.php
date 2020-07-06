<?php


namespace Mapbender\Component\Loader;


use Mapbender\Component\SourceLoader;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\Exception\Loader\RefreshTypeMismatchException;
use Mapbender\Exception\Loader\SourceLoaderException;

abstract class RefreshableSourceLoader extends SourceLoader
{
    /**
     * @param Source $target
     * @param HttpOriginInterface $origin
     * @throws \Mapbender\CoreBundle\Component\Exception\XmlParseException
     * @throws SourceLoaderException
     */
    public function refresh(Source $target, HttpOriginInterface $origin)
    {
        $reloadedSource = $this->evaluateServer($origin)->getSource();
        $this->updateSource($target, $reloadedSource);
        $this->updateOrigin($target, $origin);
    }

    /**
     * @param Source $target
     * @param Source $reloaded
     * @throws RefreshTypeMismatchException
     */
    protected function beforeSourceUpdate(Source $target, Source $reloaded)
    {
        if ($target->getType() !== $reloaded->getType()) {
            $message = "Source type mismatch: {$target->getType()} (old) vs {$reloaded->getType()} (reloaded)";
            throw new RefreshTypeMismatchException($message);
        }
    }

    abstract public function updateSource(Source $target, Source $reloaded);

    /**
     * @param Source $target
     * @return string
     */
    abstract public function getRefreshUrl(Source $target);
}
