<?php


namespace Mapbender\CoreBundle\Component;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service registered into container at mapbender.uploads_manager.service
 */
class UploadsManager
{
    /** @var string */
    protected $absoluteWebPath;
    protected string $relativePath;

    /**
     * @param Filesystem $fileSystem
     * @param string $absoluteWebPath
     * @param string $relativePath under $absoluteWebPath where uploads will be stored / looked for
     */
    public function __construct(protected Filesystem $fileSystem, $absoluteWebPath, $relativePath)
    {
        $absoluteWebPath = realpath($absoluteWebPath);
        if (!$absoluteWebPath || !is_dir($absoluteWebPath)) {
            throw new \RuntimeException("Path '{$absoluteWebPath}' doesn't exist or is not a directory");
        }
        $this->absoluteWebPath = $absoluteWebPath;
        $this->relativePath = trim($relativePath ?: '', '/\\');
    }

    /**
     * @param bool $create
     * @return string
     * @throws IOException
     */
    public function getAbsoluteBasePath($create): string|false
    {
        $fullPath = $this->absoluteWebPath . '/' . $this->relativePath;
        if ($create) {
            $this->fileSystem->mkdir($fullPath);
            return realpath($fullPath);
        } else {
            return $fullPath;
        }
    }

    /**
     * @param bool $create
     * @return string
     * @throws IOException
     */
    public function getWebRelativeBasePath($create)
    {
        if ($create) {
            $this->getAbsoluteBasePath(true);
        }
        return $this->relativePath;
    }

    /**
     * Returns the absolute path name of an uploads subdirectory, optionally creates it.
     *
     * @param string $path
     * @param bool $create
     * @return string
     * @throws IOException if $create=true and creation failed
     */
    public function getSubdirectoryPath($path, $create): string|false
    {
        $fullPath = $this->getAbsoluteBasePath(false) . "/" . trim($path, '/\\');
        if ($create) {
            $this->fileSystem->mkdir($fullPath);
            return realpath($fullPath);
        } else {
            return $fullPath;
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @throws IOException
     */
    public function copySubdirectory($from, $to): void
    {
        $absFrom = $this->getSubdirectoryPath($from, false);
        // FileSystem::mirror will not create the target directory if the source is empty, so
        // we do it ourselves
        $absTo = $this->getSubdirectoryPath($to, true);
        if ($this->fileSystem->exists($absFrom) && is_dir($absFrom) && is_readable($absFrom)) {
            $this->fileSystem->mirror($absFrom, $absTo, null, [
                'delete' => true,
                'override' => true,
                'copy_on_windows' => true,
            ]);
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $create to create $to directory even if $from is missing
     * @return string absolute filesystem path to the newly renamed directory
     * @throws IOException
     */
    public function renameSubdirectory($from, $to, $create)
    {
        $absFrom = $this->getSubdirectoryPath($from, false);
        if (!is_dir($absFrom) || !is_readable($absFrom)) {
            if (!$create) {
                throw new IOException("Source path {$absFrom} missing or not readable");
            } else {
                return $this->getSubdirectoryPath($to, true);
            }
        } else {
            $absTo = $this->getSubdirectoryPath($to, false);
            $this->fileSystem->rename($absFrom, $absTo);
            return $absTo;
        }
    }

    public function removeSubdirectory($path): void
    {
        $absPath = $this->getSubdirectoryPath($path, false);
        $this->fileSystem->remove($absPath);
    }
}
