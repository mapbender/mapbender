<?php


namespace Mapbender\CoreBundle\Component\Cache;


use Mapbender\CoreBundle\Component\Exception\NotCachable;
use Mapbender\CoreBundle\Entity\Application;
use Psr\Log\LoggerInterface;

/**
 * Caching service for assorted Mapbender Application-specific raw string data values.
 * Storage backend pluggable via container.
 *
 * First implementation only handles the frontend configuration and defaults to a filesystem backend.
 *
 * Instance registered in container as mapbender.presenter.application.cache (see services.xml)
 */
class ApplicationDataService
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Backend\File */
    protected $backend;

    /** @var float */
    protected $containerTimestamp;

    /**
     * ApplicationDataService constructor.
     * @param LoggerInterface $logger
     * @param Backend\File $backend
     * @param float $containerTimestamp
     */
    public function __construct(LoggerInterface $logger, $backend, $containerTimestamp)
    {
        $this->logger = $logger;
        $this->backend = $backend;
        $this->containerTimestamp = $containerTimestamp;
    }

    /**
     * Sends data directly to the browser if available in cache, including appropriate headers.
     * If no cached configuration is available, emits nothing and returns false.
     *
     * @param Application $application
     * @param string[] $keyPath
     * @param string $mimeType
     * @return boolean true if emission performed, false if no cache data available.
     */
    public function emitValue(Application $application, $keyPath, $mimeType)
    {
        try {
            $mark = $this->getMark($application);
            $fullKeyPath = array_merge(array($application->getSlug()), $keyPath);
            return $this->backend->emit($fullKeyPath, $mark, $mimeType);
        } catch (NotCachable $e) {
            return false;
        }
    }

    /**
     * @param Application $application
     * @param string[] $keyPath
     * @param string $value
     */
    public function putValue(Application $application, $keyPath, $value)
    {
        try {
            $mark = $this->getMark($application);
            $fullKeyPath = array_merge(array($application->getSlug()), $keyPath);
            $this->backend->put($fullKeyPath, $value, $mark);
        } catch (NotCachable $e) {
            // do nothing
        }
    }

    /**
     * Generates the reusability mark for application data.
     * Considers application "updated" column plus container compilation time, so any change to the application
     * or any configuration file makes the cache stale.
     *
     * @param Application $application
     * @return array
     */
    protected function getMark(Application $application)
    {
        $parts = array();
        if ($this->containerTimestamp !== null) {
            $parts[] = $this->containerTimestamp;
        }
        $updated = $application->getUpdated();
        if ($updated) {
            // NOTE: $updated is only available on DB applications, always NULL for yaml applications.
            //       Including the container compilation time makes caching safe even for yaml applications though,
            //       because any edit to any application's yml triggers a container re-compilation, which is reflected
            //       in the container timestamp.
            $parts[] = $updated->format('U-u');
        }
        if (!$parts) {
            throw new NotCachable();
        }
        return $parts;
    }
}
