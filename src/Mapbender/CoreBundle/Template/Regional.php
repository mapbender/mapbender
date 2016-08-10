<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Class Regional
 *
 * @package   Mapbender\CoreBundle\Template
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class Regional extends Responsive
{
    protected static $title   = "Regional";
    protected static $regions = array('top', 'left', 'center', 'right', 'bottom');
    protected static $js      = array(
        '/components/underscore/underscore-min.js',
        '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
        '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
        '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
        "/components/datatables/media/js/jquery.dataTables.min.js",
        '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
        "/components/vis-ui.js/vis-ui.js-built.js",
        '@MapbenderCoreBundle/Resources/public/js/responsive.js'
    );

    public $twigTemplate = 'MapbenderCoreBundle:Template:regional.html.twig';
}
