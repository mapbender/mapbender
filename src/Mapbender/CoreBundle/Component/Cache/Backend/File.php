<?php


namespace Mapbender\CoreBundle\Component\Cache\Backend;
use Mapbender\BaseKernel;
use Mapbender\CoreBundle\Component\Exception\CacheMiss;
use Mapbender\CoreBundle\Component\Exception\NotCachable;
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
    // constants related to internal header construction
    const H_LENGTHOF_SIGNATURE_LENGTH = 6;
    const H_LENGTHOF_VALUE_LENGTH = 8;          // <100MB max cachable value length

    /** @var string */
    protected $rootPath;

    /**
     * Lazy-initialized sprintf pattern used to generate the header plus value we place / expect in the file.
     * @var string|null
     */
    protected $storagePattern;


    /**
     * @param string $rootPath of all cache files
     * @throws \RuntimeException if $rootPath is not writable
     */
    public function __construct($rootPath)
    {
        $rootPath = realpath($rootPath);
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
        // Bake the $signature completely into the stored value. This is done to
        // 1) achieve complete atomicity of put / get
        // 2) be independent of various mtime resolutions on various filesystems when using time stamps
        // 3) support arbitrary (any serializable) signatures instead of just timestamps
        // We also encode the length of the signature and the data itself in a fixed-length fashion, to ease reading
        if (!$this->storagePattern) {
            $this->storagePattern = '%' . static::H_LENGTHOF_SIGNATURE_LENGTH . 'd'
                                  . ':'
                                  . '%' . static::H_LENGTHOF_VALUE_LENGTH . 'd'
                                  // two more placeholders for signature + value
                                  . ':%s:%s';
        }
        $valueLength = strlen($value);
        if (strlen($valueLength) > static::H_LENGTHOF_VALUE_LENGTH) {
            throw new NotCachable("Value too long to encode in header ($valueLength bytes)");
        }
        $valueInternal = sprintf($this->storagePattern, strlen($signature), $valueLength, $signature, $value);
        $fullPath = $this->getFullPath($keyPath);
        @mkdir(dirname($fullPath), 0777, true);
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
        $fullPath = $this->getFullPath($keyPath);

        // Header encodes lengths of signature and value plus two semicolon separators
        $headerLength = static::H_LENGTHOF_SIGNATURE_LENGTH + static::H_LENGTHOF_VALUE_LENGTH + 2;
        // The absolute minimum length of the whole internal value is the header + empty signature + semicolon + empty value
        $minimumLength = $headerLength + 2;

        $valueInternal = @file_get_contents($fullPath);
        if (!$valueInternal || strlen($valueInternal) < $minimumLength) {
            throw new CacheMiss();
        }
        $header = substr($valueInternal, 0, $headerLength);
        $signatureLength = intval(substr($header, 0, static::H_LENGTHOF_SIGNATURE_LENGTH));
        $cachedSignature = substr($valueInternal, $headerLength, $signatureLength);
        if ($cachedSignature === false || $cachedSignature !== $signature) {
            throw new CacheMiss();
        }
        // value length is encoded after signature length and a semicolon
        $valueLengthStart = static::H_LENGTHOF_SIGNATURE_LENGTH + 1;
        $valueLength = intval(substr($header, $valueLengthStart, static::H_LENGTHOF_VALUE_LENGTH));
        // value starts after signature plus a semicolon
        $valueStart = $headerLength + $signatureLength + 1;
        if (strlen($valueInternal) < ($valueStart + $valueLength)) {
            // file is truncated or otherwise corrupt OR our header formatting constants may have changed
            // since it was written
            throw new CacheMiss();
        }

        $value = substr($valueInternal, $valueStart, $valueLength);
        if ($value === false) {
            // If substr decides to fail ...
            throw new CacheMiss();
        }
        return $value;
    }

    /**
     * @param string[] $keyPath
     * @return string
     */
    protected function getFullPath($keyPath)
    {
        // prepend root path
        array_unshift($keyPath, $this->rootPath);
        return implode('/', $keyPath);
    }
}
