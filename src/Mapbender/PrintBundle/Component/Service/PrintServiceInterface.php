<?php


namespace Mapbender\PrintBundle\Component\Service;


interface PrintServiceInterface
{
    /**
     * Builds and dumps the (PDF) document to binary string in one step
     *
     * @param array $printJobData
     * @return string
     */
    public function dumpPrint(array $printJobData);

    /**
     * Builds and dumps the (PDF) document to a file in one step. Depending on PDF library,
     * this could potentially be more memory efficient vs extracting the binary contents
     * first.
     *
     * @param array $printJobData
     * @param string $fileName
     * @return string
     */
    public function storePrint(array $printJobData, $fileName);
}
