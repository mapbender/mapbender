<?php
namespace Mapbender\ManagerBundle\Component;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Form model for ImportJobType
 *
 * @author Paul Schmidt
 * @author Andriy Oblivantsev
 */
class ImportJob extends ExchangeJob
{

    /** @var UploadedFile|File|null File to be imported */
    protected $importFile;

    /**
     * @return null|File|UploadedFile
     */
    public function getImportFile()
    {
        return $this->importFile;
    }

    /**
     * @param File|UploadedFile $importFile
     * @return $this
     */
    public function setImportFile(File $importFile)
    {
        $this->importFile = $importFile;
        return $this;
    }
}
