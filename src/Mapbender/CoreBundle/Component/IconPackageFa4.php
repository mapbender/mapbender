<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconPackageFa4 implements IconPackageInterface
{
    public function getStyleSheets()
    {
        return array(
            'components/font-awesome/css/font-awesome.min.css',
        );
    }

    public function getChoices()
    {
        return array(
            /** @todo: localize labels */
            /** @todo: remove technocratic "(FontAwesome)" label postfixes */
            'About (FontAwesome)' => 'iconAbout',
            'Area ruler (FontAwesome)' => 'iconAreaRuler',
            'Feature info (FontAwesome)' => 'iconInfoActive',
            'GPS (FontAwesome)' => 'iconGps',
            'Home (FontAwesome)' => 'iconHome',
            'Legend (FontAwesome)' => 'iconLegend',
            'Print (FontAwesome)' => 'iconPrint',
            'Search (FontAwesome)' => 'iconSearch',
            'Layer tree (FontAwesome)' => 'iconLayertree',
            'Logout (FontAwesome)' => 'iconLogout',
            'WMS (FontAwesome)' => 'iconWms',
            'Help (FontAwesome)' => 'iconHelp',
            'Edit (FontAwesome)' => 'iconEdit',     // = formerly iconWmcEditor, iconSketch
            'WMC Loader (FontAwesome)' => 'iconWmcLoader',
            'Coordinates (FontAwesome)' => 'iconCoordinates',
            'Gps Target (FontAwesome)' => 'iconGpsTarget',
            'POI (FontAwesome)' => 'iconPoi',
            'Image Export (FontAwesome)' => 'iconImageExport',
            'Copyright (FontAwesome)' => 'iconCopyright',
            'Share' => 'iconShare',
            'Refresh' => 'iconRefresh',
        );
    }

    public function getIconMarkup($iconCode)
    {
        switch ($iconCode) {
            default:
                throw new \LogicException("Unhandled icon code " . \var_export($iconCode, true));
            case 'iconAbout':
                $class = 'fa fas fa-users'; break;
            case 'iconAreaRuler':
                $class = 'fa fas fa-crop'; break;
            case 'iconInfoActive':
                $class = 'fa fas fa-info-circle'; break;
            case 'iconGps':
                /** @todo FA5: confirm result is fa-map-marker-alt glyph (poked hole) */
                $class = 'fa fas fa-map-marker-alt fa-map-marker'; break;
            case 'iconHome':
                $class = 'fa fas fa-home'; break;
            case 'iconLegend':
                $class = 'fa fas fa-th-list'; break;
            case 'iconLogout':
                $class = 'fa fas fa-sign-out'; break;
            case 'iconPrint':
                $class = 'fa fas fa-print'; break;
            case 'iconSearch':
                $class = 'fa fas fa-search'; break;
            case 'iconLayertree':
                $class = 'fa fas fa-sitemap'; break;
            case 'iconWms':
                /** @todo FA5: prefer fa-globe-americas..? (same result as FA4 fa-globe) */
                $class = 'fa fas fa-globe'; break;
            case 'iconHelp':
                $class = 'fa fas fa-question-circle'; break;
            case 'iconEdit':
            case 'iconWmcEditor':
            case 'iconSketch':
                $class = 'fa fa-edit'; break;
            case 'iconWmcLoader':
                $class = 'fa fas fa-folder-open'; break;
            case 'iconCoordinates':
            case 'iconGpsTarget':
                $class = 'fa fas fa-crosshairs'; break;
            case 'iconPoi':
                $class = 'fa fas fa-thumbtack fa-thumb-tack'; break;
            case 'iconImageExport':
                $class = 'fa fas fa-camera'; break;
            case 'iconCopyright':
                $class = 'fa far fa-copyright'; break;
            case 'iconShare':
                $class = 'fa fas fa-share-alt'; break;
            case 'iconRefresh':
                $class = 'fa fas fa-sync-alt fa-refresh'; break;
        }
        return HtmlUtil::renderTag('i', '', array(
            'class' => $class,
        ));
    }

    public function isHandled($iconCode)
    {
        return \in_array($iconCode, $this->getChoices()) || \array_key_exists($iconCode, $this->getAliases());
    }

    public function getAliases()
    {
        return array(
            'iconWmcEditor' => 'iconEdit',
            'iconSketch' => 'iconEdit',
            'iconGpsTarget' => 'iconCoordinates',
            'iconReset' => 'iconRefresh',
        );
    }
}
