<?php


namespace Mapbender\PrintBundle\Component\Service;


interface PrintServiceInterface
{
    public function buildPdf(array $printJobData);
}
