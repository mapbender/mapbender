<?php


namespace Mapbender\CoreBundle\Component\Cache;


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
     * Returns, if available in cache, the json-serialized configuration as a single string. If not cached, or cache
     * held stale non-reusable data, returns boolean false.
     *
     * @param Application $application
     * @return string|false
     */
    public function getConfigurationJson(Application $application)
    {
        $mark = $this->getMark($application);
        return $this->backend->get(array($application->getSlug(), 'config.json'), $mark);
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
        $fullKeyPath = array_merge(array($application->getSlug()), $keyPath);
        $mark = $this->getMark($application);
        return $this->backend->emit($fullKeyPath, $mark, $mimeType);
    }

    /**
     * @param Application $application
     * @param string[] $keyPath
     * @param string $value
     */
    public function putValue(Application $application, $keyPath, $value)
    {
        $fullKeyPath = array_merge(array($application->getSlug()), $keyPath);
        $mark = $this->getMark($application);
        $this->backend->put($fullKeyPath, $value, $mark);
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
        $parts = array(
            $this->containerTimestamp,
        );
        $updated = $application->getUpdated();
        if ($updated) {
            // NOTE: $updated is only available on DB applications, always NULL for yaml applications.
            //       Including the container compilation time makes caching safe even for yaml applications though,
            //       because any edit to any application's yml triggers a container re-compilation, which is reflected
            //       in the container timestamp.
            $parts[] = $updated->format('U-u');
        }
        return $parts;
    }
}
