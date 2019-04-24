<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search engine interface for use by search classes used by SearchRouter
 * element.
 *
 * @author Christian Wygoda
 * @deprecated and well
 */
interface SearchEngine
{

    /**
     * Constructor, receives the DI container for access to everything else.
     * 
     * @param ContainerInterface $container DI container
     */
    public function __construct(ContainerInterface $container);

    /**
     * Autocomplete handler
     * 
     * @param  string $target Field to autocomple for
     * @param  string $term   Term to autocomplete for
     * @param  array  $data   Values of all form fields
     * @param  string $srs    current map srs
     * @param  array  $extent current map extent
     * @return array          Autocomplete suggestions with label, value and
     *                        optionally key attributes
     */
    public function autocomplete($target, $term, $data, $srs, $extent);

    /**
     * Search handler
     * @param  array  $conf   Form configuration
     * @param  array  $data   Data: Array with form data array
     * @param  string $srs    current map srs
     * @param  array  $extent current map extent
     * @return array          Result set array 
     */
    public function search(array $conf, array $data, $srs, $extent);
}