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

    /**
     * ApplicationDataService constructor.
     * @param LoggerInterface $logger
     * @param Backend\File $backend
     */
    public function __construct(LoggerInterface $logger, $backend)
    {
        $this->logger = $logger;
        $this->backend = $backend;
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
     * @todo: container compilation time should also be considered (for "app_dev" mode)
     *
     * @param Application $application
     * @return string
     */
    protected function getMark(Application $application)
    {
        $updated = $application->getUpdated();
        // NOTE: $updated is NULL for yaml applications ...
        if ($updated) {
            return $updated->format('U-u');
        } else {
            return 'never';
        }
    }
}
