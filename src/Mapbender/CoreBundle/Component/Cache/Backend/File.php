<?php


namespace Mapbender\CoreBundle\Component\Cache\Backend;
use Mapbender\BaseKernel;
use Mapbender\CoreBundle\Component\Exception\CacheMiss;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cache backend using the file system.
 * This uses the default Symfony cache directories also used by twig, doctrine etc as the base storage location.
 *
 * Cache backend provides raw key:string value storage and handles automatic invalidation based on signatures
 * (which can be timestamps, but any arbitrary string will work).
 *
 * Instance registered in container at mapbender.presenter.application.cache.backend (see serviccs.xml)
 */
class File
{
    /** @var string */
    protected $rootPath;

    public function __construct(ContainerInterface $container)
    {
        /** @var BaseKernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = realpath($kernel->getCacheDir());
        if (!is_dir($rootPath) || !is_writable($rootPath)) {
            throw new \RuntimeException("Cache path {$rootPath} is not writable");
        }
        $this->rootPath = $rootPath;
    }

    /**
     * @param string[] $keyPath a multi-component key, in this backend used as a subdirectory hierarchy
     * @param string $value will be stored exactly as given
     * @param string $signature to determine reusability of the cache entry later in get
     */
    public function put($keyPath, $value, $signature)
    {
        // prepend root path
        array_unshift($keyPath, $this->rootPath);
        $fullPath = implode('/', $keyPath);
        @mkdir(dirname($fullPath));
        // Bake the $signature completely into the stored value. This is done to
        // 1) achieve complete atomicity of put / get
        // 2) be independent of various mtime resolutions on various filesystems when using time stamps
        // 3) support arbitrary (any serializable) signatures instead of just timestamps

        // Encode the length of the signature and the data itself in a fixed-length fashion, to ease reading
        $prefix = sprintf("%6d:%6d:%s:", strlen($signature), strlen($value), $signature);
        $valueInternal = $prefix . $value;
        @file_put_contents($fullPath, $valueInternal, LOCK_EX);
    }

    /**
     * Retrieves a stored value from the cache, if reusable.
     *
     * @param string[] $keyPath multi-component key
     * @param string $signature to verify reusability
     * @return string|false string on hit, boolean false on miss
     */
    public function get($keyPath, $signature)
    {
        try {
            return $this->getInternal($keyPath, $signature);
        } catch (CacheMiss $e) {
            // @todo: should this be allowed to bubble?
            return false;
        }
    }

    /**
     * @param string[] $keyPath multi-component key
     * @param string $signature
     * @return string
     * @throws CacheMiss
     */
    protected function getInternal($keyPath, $signature)
    {
        array_unshift($keyPath, $this->rootPath);
        $fullPath = implode('/', $keyPath);

        $valueInternal = @file_get_contents($fullPath);
        if (!$valueInternal || strlen($valueInternal) < 16) {
            throw new CacheMiss();
        }
        $header = substr($valueInternal, 0, 14);
        $signatureLength = intval(substr($header, 0, 6));
        $cachedSignature = substr($valueInternal, 14, $signatureLength);
        if ($cachedSignature === false || $cachedSignature !== $signature) {
            throw new CacheMiss();
        }
        $valueLength = intval(substr($header, 7, 6));
        $valueStart = 14 + $signatureLength + 1;
        if (strlen($valueInternal) < ($valueStart + $valueLength)) {
            throw new CacheMiss();
        }

        return substr($valueInternal, $valueStart, $valueLength);
    }
}
