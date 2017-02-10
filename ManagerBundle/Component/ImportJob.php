<?php
namespace Mapbender\ManagerBundle\Component;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Parser;

/**
 * Description of ExportJob
 *
 * @author Paul Schmidt
 * @author Andriy Oblivantsev
 */
class ImportJob extends ExchangeJob
{

    /** @var bool Should apply application? */
    protected $addApplication;

    /** @var UploadedFile|File|null File to be imported */
    protected $importFile;

    /** @var array Application configuration array */
    protected $importContent;

    /**
     * ExchangeJob constructor.
     *
     * @param string $format
     */
    public function __construct($format = null)
    {
        parent::__construct($format);
        $this->addApplication = true;
        $this->importFile     = null;
    }

    /**
     * @return bool
     */
    public function getAddApplication()
    {
        return $this->addApplication;
    }

    /**
     * @param $addApplication
     * @return $this
     */
    public function setAddApplication($addApplication)
    {
        $this->addApplication = $addApplication;
        return $this;
    }

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

    /**
     * Get application configuration as array
     *
     * @return mixed
     */
    public function getImportContent()
    {
        if (!$this->importContent && $this->getImportFile()) {
            $this->importContent();
        }
        return $this->importContent;
    }

    /**
     * @param $importContent
     * @return $this
     */
    public function setImportContent($importContent)
    {
        $this->importContent = $importContent;
        return $this;
    }

    /**
     * Import application configuration from file.
     *
     * @return $this
     */
    protected function importContent()
    {
        $scFile = $this->getImportFile();
        $yaml   = new Parser();
        $this->setImportContent(
            $yaml->parse(
                file_get_contents($scFile->getRealPath()
                )
            )
        );
        return $this;
    }
}
