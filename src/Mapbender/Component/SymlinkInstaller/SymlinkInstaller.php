<?php
namespace Mapbender\Component\SymlinkInstaller;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SymlinkInstaller
 *
 * @package Mapbender\Component\SymlinkInstaller
 */
class SymlinkInstaller implements SymlinkInstallerInterface
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    private $filesystem = null;
    private $originDir = null;
    private $targetDir = null;
    private $relativeOriginDir = null;

    private $methodSymlinksAreInstalledBy;

    /**
     * SymlinkInstaller constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Directory a symlink is created from
     *
     * @param string $originDir
     * @return $this
     */
    public function setOriginDir($originDir)
    {
        $this->originDir = $originDir;

        return $this;
    }

    /**
     * Directory where a symlink will be created
     *
     * @param string $targetDir
     * @return $this
     */
    public function setTargetDir($targetDir)
    {
        $this->targetDir = $targetDir;

        return $this;
    }

    /**
     * Install symlinks by method
     *
     * @param string $installationMethod
     */
    public function installSymlinks($installationMethod)
    {
        switch ($installationMethod) {
            case self::METHOD_RELATIVE_SYMLINK:
                $this->relativeSymlinkWithFallback();
                break;
            case self::METHOD_ABSOLUTE_SYMLINK:
                $this->absoluteSymlinkWithFallback();
                break;
            default:
                $this->hardCopy();
        }
    }

    /**
     * Try to create relative symlink
     *
     * Falling back to absolute symlink and finally hard copy
     */
    public function relativeSymlinkWithFallback()
    {
        try {
            $this->setRelativeOriginDir();
            $this->symlink();
        } catch (IOException $e) {
            $this->absoluteSymlinkWithFallback();
        }

        $this->methodSymlinksAreInstalledBy = self::METHOD_RELATIVE_SYMLINK;
    }

    /**
     * Try to create absolute symlink
     *
     * Falling back to hard copy
     */
    public function absoluteSymlinkWithFallback()
    {
        try {
            $this->symlink();
        } catch (IOException $e) {
            $this->hardCopy();
        }

        $this->methodSymlinksAreInstalledBy = self::METHOD_ABSOLUTE_SYMLINK;
    }

    /**
     * Creates symbolic link
     *
     * @throws IOException If link can not be created.
     */
    public function symlink()
    {
        $this->checkSettings();

        $this->filesystem->symlink($this->relativeOriginDir ?? $this->originDir, $this->targetDir);

        if (!file_exists($this->targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $this->targetDir), 0, null, $this->targetDir);
        }
    }

    /**
     * Copies origin to target
     */
    public function hardCopy()
    {
        $this->checkSettings();

        $this
            ->filesystem
            ->mkdir($this->targetDir, 0777);

        $this
            ->filesystem
            ->mirror(
                $this->originDir,
                $this->targetDir,
                Finder::create()->ignoreDotFiles(false)->in($this->originDir)
            );

        $this->methodSymlinksAreInstalledBy = self::METHOD_COPY;
    }

    /**
     * Set relative origin dir using relative path of origin folder
     */
    private function setRelativeOriginDir()
    {
        $this->relativeOriginDir = $this->filesystem->makePathRelative($this->originDir, realpath(dirname($this->targetDir)));
    }

    /**
     * Check if all necessary settings are set
     */
    private function checkSettings()
    {
        $this
            ->isSetFilesystem()
            ->isSetOriginDir()
            ->isSetTargetDir();
    }

    /**
     * Check if a filesystem is set
     *
     * @return $this
     * @throws \Exception
     */
    private function isSetFilesystem()
    {
        if (null === $this->filesystem) {
            throw new \Exception('File system is not set');
        }

        return $this;
    }

    /**
     * Check if an origin directory path is set
     *
     * @return $this
     * @throws \Exception
     */
    private function isSetOriginDir()
    {
        if (null === $this->originDir) {
            throw new \Exception('Origin symlink folder is not set');
        }

        return $this;
    }

    /**
     * Check if target directory path is set
     *
     * @return $this
     * @throws \Exception
     */
    private function isSetTargetDir()
    {
        if (null === $this->targetDir) {
            throw new \Exception('Target symlink folder is not set');
        }

        return $this;
    }

    /**
     * Get method files are installed by
     *
     * @return string mixed
     */
    public function getMethodSymlinksAreInstalledBy()
    {
        return $this->methodSymlinksAreInstalledBy;
    }
}