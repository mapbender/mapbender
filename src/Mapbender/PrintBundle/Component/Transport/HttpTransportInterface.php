<?php


namespace Mapbender\PrintBundle\Component\Transport;


use Symfony\Component\HttpFoundation\Response;

interface HttpTransportInterface
{
    /**
     * @param string $url
     * @return Response
     */
    public function getUrl($url);
}
