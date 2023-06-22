<?php
namespace FOM\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Response;
use Shuchkin\SimpleXLSXGen;

/**
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class ExportResponse extends Response
{
    /** Unicode charset */
    const UTF_16_LE = 'UTF-16LE';

    /** @var string Enclosure */
    private $enclosure;

    /** @var string Delimiter */
    private $delimiter;

    /** @var string Encoding from */
    private $encodingFrom;

    /** @var string File name */
    private $fileName;

    /** Excel export type */
    const TYPE_XLS = 'xls';

      /** Excel export type */
    const TYPE_XLSX = 'xlsx';

    /** CSV export type */
    const TYPE_CSV = 'csv';

    /** @var string */
    private $type;

    /** Excel int type  */
    const XLS_INT_TYPE    = 0x203;

    /** Excel string type  */
    const XLS_STRING_TYPE = 0x204;

    /**
     * Export Response
     *
     * @param array  $data           Data list
     * @param string $fileName       Name of file
     * @param string $type
     * @param string $encodingFrom   Encode from charset
     * @param string $enclosure      Enclosure
     * @param string $delimiter      Delimiter
     * @param bool   $enableDownload Enable download
     */
    public function __construct(array $data = NULL, $fileName = 'export',  $type = self::TYPE_CSV, $encodingFrom = 'UTF-8', $enclosure = '"', $delimiter = ',', $enableDownload = true)
    {
        parent::__construct();
        $this->setEncodingFrom($encodingFrom);
        $this->setType($type);

        switch($type){
            case self::TYPE_CSV:
                $this->setDelimiter($delimiter);
                $this->setEnclosure($enclosure);
                $this->setFileName($fileName.".csv");
                if ($data) {
                    $this->setCsv($data);
                }

                break;

            case self::TYPE_XLS:
                $this->setFileName($fileName.".xls");
                if ($data) {
                    $this->setXls($data);
                }
                break;
            
            case self::TYPE_XLSX:
                $this->setFileName($fileName.".xlsx");
                if ($data) {
                    $this->setXlsx($data);
                }
                break;
        }

        if($enableDownload){
            $this->enableDownload();
        }
    }

    /**
     * Enable download
     */
    public function enableDownload()
    {
        $this->headers->add(array('Cache-Control' => 'private',
                                  'Pragma'        => 'no-cache',
                                  'Expires'       => '0')
        );
    }

    /**
     * Disable  download
     */
    public function disableDownload(){
        $this->headers->remove('Cache-Control');
        $this->headers->remove('Pragma');
        $this->headers->remove('Expires');
    }

    /**
     * Generate Excel data sheet
     *
     * @param $data
     */
    public function setXls(array &$data){
        $output = self::genXLS($data);
        $this->setData($output);
    }

     /**
     *
     * @param array $data
     * @return void
     */

    public function setXlsx(array &$data){
        $xlsx = SimpleXLSXGen::fromArray( $data );
        $this->setData($xlsx);
    }

    /**
     * Generate CSV list
     *
     * @param array $data
     * @param bool  $detectHead
     * @internal param bool $xls
     */
    public function setCsv(array &$data, $detectHead = true)
    {
        $handle     = self::createMemoryHandle();
        if($detectHead && count($data)> 0){
            fputcsv($handle, array_keys($data[0]), $this->delimiter, $this->enclosure);
        }
        foreach ($data as $row) {
            fputcsv($handle, $row, $this->delimiter, $this->enclosure);
        }
        rewind($handle);
        $output = chr(255) . chr(254
            ) . mb_convert_encoding('sep=' . $this->delimiter . "\n" . stream_get_contents($handle),
                self::UTF_16_LE,
                $this->encodingFrom
            );
        $this->setData($output);
        fclose($handle);
    }

    /**
     * @param mixed $output
     * @return $this
     */
    public function setData(&$output){
        $this->headers->add(array('Content-Length' => strlen($output)));
        $this->setContent($output);
        return $this;
    }

    /**
     * Set export type
     *
     * @param string $type
     */
    public function setType($type)
    {
        switch ($type) {
            case self::TYPE_CSV:
                $this->headers->add(array('Content-Type' => 'text/csv;charset=' . self::UTF_16_LE));
                break;
            case self::TYPE_XLS:
                $this->headers->add(array("Content-Type" => "application/vnd.ms-excel"));
                break;
            case self::TYPE_XLSX:
                $this->headers->add(array("Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"));
                break;
        }
        $this->type = $type;
    }

    /**
     * Set enclosure
     *
     * @param string $enclosure
     * @return $this
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Set delimiter
     *
     * @param string $delimiter
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Set encoding from charset
     *
     * @param string $encodingFrom
     * @return $this
     */
    public function setEncodingFrom($encodingFrom)
    {
        $this->encodingFrom = $encodingFrom;
        return $this;
    }

    /**
     * Set export file name
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        $this->headers->add(array('Content-Disposition' => 'attachment; filename="' . $this->fileName . '"'));
        return $this;
    }

    /**
     * Here's the shortest and the fastest way to generate Excel file.
     * It supports string and numeric fields.
     * I hope this function can avoid you using huge Excel PHP classes which are too complicated
     * and slow (and require reading a lot of documentation) for such a basic task.
     *
     * @param array          $data list of key/value array
     * @param null| resource $handle
     * @param bool           $detectHead detect and write a head
     * @return null|resource|string if resource not given returns a string
     */
    public static function genXLS(array &$data, $handle = null, $detectHead = true)
    {
        $returnString = !$handle;

        if(!$handle){
            $handle  = self::createMemoryHandle();
        }

        /* write Excel BOF */
        fputs($handle, pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0));
        $rowNum = 0;


        /* write head */
        if($detectHead && count($data)> 0){
            $colNum = 0;
            $keys = array_keys($data[0]);
            $hasKeys = false;

            /* check if has some key names */
            foreach ($keys as $keyName){
                if(!is_numeric($keyName)){
                    $hasKeys = true;
                    break;
                }
            }

            if ($hasKeys) {
                foreach ($keys as $key => $value) {
                    $value = utf8_decode($value);
                    $l = strlen($value);
                    fputs($handle, pack("ssssss", self::XLS_STRING_TYPE, 8 + $l, $rowNum, $colNum, 0x0, $l) . $value);
                    $colNum++;
                }
                $rowNum++;
            }
        }

        /* write list */
        foreach ($data as $row) {
            $colNum = 0;
            foreach ($row as $keyName => $value) {
                $value = utf8_decode(trim($value));

                /* string cell */
                if (!is_numeric($value)) {
                    $l = strlen($value);
                    fputs($handle,pack("ssssss", self::XLS_STRING_TYPE, 8 + $l, $rowNum, $colNum, 0x0, $l) . $value);
                } /* numeric cell */ else {
                    fputs($handle,pack("sssss", self::XLS_INT_TYPE, 14, $rowNum, $colNum, 0x0) . pack("d", $value));
                }
                $colNum++;
            }
            $rowNum++;
        }

        /* write Excel EOF */
        fputs($handle, pack("ss", 0x0A, 0x00));

        if($returnString){
            rewind($handle);
            $r = stream_get_contents($handle);
            fclose($handle);
        }else{
            $r = $handle;
        }

        return $r;
    }

    /**
     * @return resource
     */
    public static function createMemoryHandle()
    {
        return fopen('php://memory', 'rb+');
    }
}
