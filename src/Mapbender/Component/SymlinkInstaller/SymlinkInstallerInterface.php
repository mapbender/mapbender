<?php
namespace Mapbender\Component\SymlinkInstaller;

use Symfony\Component\Filesystem\Exception\IOException;

interface SymlinkInstallerInterface
{
    /**
     * Directory a symlink is created from
     *
     * @param string $originDir
     * @return $this
     */
    public function setOriginDir($originDir);

    /**
     * Directory where a symlink will be created
     *
     * @param string $targetDir
     * @return $this
     */
    public function setTargetDir($targetDir);

    /**
     * Install symlinks by method
     *
     * @param string $installationMethod
     */
    public function installSymlinks($installationMethod);

    /**
     * Creates symbolic link.
     *
     * @throws IOException If link can not be created.
     */
    public function symlink();

    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.     *
     *
     * @return string
     */
    public function relativeSymlinkWithFallback();

    /**
     * Try to create absolute symlink.
     *
     * Falling back to hard copy.
     *
     * @return string
     */
    public function absoluteSymlinkWithFallback();

    /**
     * Copies origin to target.
     *
     * @return string
     */
    public function hardCopy();

    /**
    * Get method files are installed by
    *
    * @return string mixed
    */
    public function getMethodSymlinksAreInstalledBy();

}