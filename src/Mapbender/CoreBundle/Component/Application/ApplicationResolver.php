<?php

namespace Mapbender\CoreBundle\Component\Application;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 *
 */
interface ApplicationResolver
{
    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getApplicationEntity(string $slug): Application;
}
