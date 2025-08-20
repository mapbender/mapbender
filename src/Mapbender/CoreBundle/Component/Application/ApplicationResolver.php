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

    /**
     * Should only be used by commands that run in the background without access to the user session.
     * Will not check access permissions to the resolved application.
     * @throws NotFoundHttpException
     */
    public function getApplicationEntityUnsecure(string $slug): Application;
}
