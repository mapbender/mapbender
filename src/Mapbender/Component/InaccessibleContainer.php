<?php


namespace Mapbender\Component;


use Symfony\Component\DependencyInjection\ContainerInterface;

class InaccessibleContainer implements ContainerInterface
{
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        throw new \LogicException("Container access not allowed in this path");
    }

    public function set($id, $service)
    {
        throw new \LogicException("Container access not allowed in this path");
    }

    public function getParameter($name)
    {
        throw new \LogicException("Container access not allowed in this path");
    }

    public function setParameter($name, $value)
    {
        throw new \LogicException("Container access not allowed in this path");
    }

    public function has($id)
    {
        return false;
    }

    public function hasParameter($name)
    {
        return false;
    }

    public function initialized($id)
    {
        return false;
    }

}
