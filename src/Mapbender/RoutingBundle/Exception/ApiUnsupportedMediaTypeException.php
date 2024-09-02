<?php

namespace Mapbender\RoutingBundle\Exception;

use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ApiUnsupportedMediaTypeException extends UnsupportedMediaTypeHttpException implements ApiException
{

}
