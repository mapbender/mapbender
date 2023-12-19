<?php
namespace FOM\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 * @deprecated
 */
class CsvResponse extends Response
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

    /**
     * CSV Response
     *
     * @param array  $data           Data list
     * @param string $fileName       Name of file
     * @param string $encodingFrom   Encode from charset
     * @param string $enclosure      Enclosure
     * @param string $delimiter      Delimiter
     * @param bool   $enableDownload Enable download
     */
    public function __construct(array $data = NULL, $fileName = 'export.csv', $encodingFrom = 'UTF-8', $enclosure = '"', $delimiter = ',', $enableDownload = true)
    {
        parent::__construct('',
            200,
            array('Content-Type'        => 'text/csv;charset=' . self::UTF_16_LE,
                  'Content-Description' => 'CSV Export')
        );

        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setEncodingFrom($encodingFrom);
        $this->setFileName($fileName);

        if($enableDownload){
            $this->enableDownload();
        }

        if ($data) {
            $this->setData($data);
        }
    }

    public function enableDownload()
    {
        $this->headers->add(array('Cache-Control' => 'private',
                                  'Pragma'        => 'no-cache',
                                  'Expires'       => '0')
        );
    }

    /**
     * Set data
     *
     * @param array $data
     * @param bool  $detectHead
     */
    public function setData(array $data, $detectHead = true)
    {
        $handle     = fopen('php://memory', 'rb+');
        if($detectHead && count($data)> 0){
            fputcsv($handle, array_keys(current($data)), $this->delimiter, $this->enclosure);
        }
        foreach ($data as $row) {
            fputcsv($handle, $row, $this->delimiter, $this->enclosure);
        }
        rewind($handle);
        $output = chr(255) . chr(254) . mb_convert_encoding('sep=' . $this->delimiter . "\n" . stream_get_contents($handle),self::UTF_16_LE,$this->encodingFrom);
        $this->headers->add(array('Content-Length' => strlen($output)));
        $this->setContent($output);
        fclose($handle);
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
}
