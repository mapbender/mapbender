<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

interface ElementHttpHandlerInterface
{
    /**
     * @param Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleRequest(Element $element, Request $request);
}
