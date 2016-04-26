<?php

namespace Mapbender\DrupalIntegrationBundle\Session;

class DrupalSessionHandler implements \SessionHandlerInterface
{
    public function __construct()
    {
        //die("BFA");
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $name)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($id)
    {
        _drupal_session_destroy($id);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        _drupal_session_garbage_collection($lifetime);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($id)
    {
        return _drupal_session_read($id);
    }

    /**
     * {@inheritDoc}
     */
    public function write($id, $data)
    {
        _drupal_session_write($id, $data);
        return true;
    }
}
