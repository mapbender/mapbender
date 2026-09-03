<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconPackageFa4 implements IconPackageInterface
{
    protected bool $showDefaultIcons = true;
    protected array $additionalIcons = [];

    public function __construct(bool $disableDefaultIcons, ?array $additionalIcons)
    {
        $this->showDefaultIcons = !$disableDefaultIcons;
        if (is_array($additionalIcons)) $this->additionalIcons = $additionalIcons;
    }


    public function getStyleSheets(): array
    {
        return ['components/font-awesome/css/all.css'];
    }

    /**
     * @return mixed[]
     */
    public function getChoices(bool $showAll = false): array
    {
        $choices = [];
        if ($this->showDefaultIcons || $showAll) $choices = [
            'mb.core.icon.fa.about' => 'iconAbout',
            'mb.core.icon.fa.accessibility' => 'iconAccessibility',
            'mb.core.icon.fa.info' => 'iconInfoActive',
            'mb.core.icon.fa.pin' => 'iconGps',
            'mb.core.icon.fa.home' => 'iconHome',
            'mb.core.icon.fa.legend' => 'iconLegend',
            'mb.core.icon.fa.print' => 'iconPrint',
            'mb.core.icon.fa.search' => 'iconSearch',
            'mb.core.icon.fa.layer_tree' => 'iconLayertree',
            'mb.core.icon.fa.logout' => 'iconLogout',
            'mb.core.icon.fa.login' => 'iconLogin',
            'mb.core.icon.fa.wms' => 'iconWms',
            'mb.core.icon.fa.folderTree' => 'iconFolderTree',
            'mb.core.icon.fa.list' => 'iconList',
            'mb.core.icon.fa.help' => 'iconHelp',
            'mb.core.icon.fa.edit' => 'iconEdit',
            'mb.core.icon.fa.wmc' => 'iconWmcLoader',
            'mb.core.icon.fa.coordinates' => 'iconCoordinates',
            'mb.core.icon.fa.poi' => 'iconPoi',
            'mb.core.icon.fa.camera' => 'iconImageExport',
            'mb.core.icon.fa.copyright' => 'iconCopyright',
            'mb.core.icon.fa.share' => 'iconShare',
            'mb.core.icon.fa.shareSquare' => 'iconShareSquare',
            'mb.core.icon.fa.forward' => 'iconShareArrow',
            'mb.core.icon.fa.refresh' => 'iconRefresh',
            'mb.core.icon.fa.earth' => 'iconEarth',
            'mb.core.icon.fa.map' => 'iconMap',
            'mb.core.icon.fa.pin_alt' => 'iconMapPin',
            'mb.core.icon.fa.mapWithPin' => 'iconMapWithPin',
            'mb.core.icon.fa.images' => 'iconImages',
            'mb.core.icon.fa.dataupload' => 'iconDataUpload',
            'mb.routing.backend.iconTitle' => 'iconRouting',
            'mb.core.icon.fa.bookmark' => 'iconBookmark',
            'mb.core.icon.fa.chartcolumn' => 'iconChartColumn',
            'mb.core.icon.fa.bookopen' => 'iconBookOpen',
            'mb.core.icon.fa.settings' => 'iconSettings',
            'mb.core.icon.fa.lightbulb' => 'iconLightbulb',
            'mb.core.icon.fa.barsStaggered' => 'iconBarsStaggered',
            'mb.core.icon.fa.circleCheck' => 'iconCircleCheck',
            'mb.core.icon.fa.cube' => 'iconCube',
            'mb.core.icon.fa.cubes' => 'iconCubes',
            'mb.core.icon.fa.envelope' => 'iconEnvelope',
        ];

        foreach ($this->additionalIcons as $icon) {
            $choices[$icon['title']] = $icon['name'];
        }

        return $choices;
    }

    public function getIconMarkup($iconCode, $additionalClass = '')
    {
        $class = null;
        foreach ($this->additionalIcons as $icon) {
            if ($icon['name'] === $iconCode) {
                $class = $icon['class'];
            }
        }

        if (!$class) {
            switch ($iconCode) {
                default:
                    throw new \LogicException("Unhandled icon code " . \var_export($iconCode, true));
                case 'iconAbout':
                    $class = 'fas fa-users';
                    break;
                case 'iconAccessibility':
                    $class = 'fas fa-universal-access';
                    break;
                case 'iconInfoActive':
                    $class = 'fas fa-circle-info';
                    break;
                case 'iconGps':
                    $class = 'fas fa-location-dot';
                    break;
                case 'iconHome':
                    $class = 'fas fa-house-chimney';
                    break;
                case 'iconLegend':
                    $class = 'fas fa-th-list';
                    break;
                case 'iconLogout':
                    $class = 'fas fa-right-from-bracket';
                    break;
                case 'iconLogin':
                    $class = 'fas fa-right-to-bracket';
                    break;
                case 'iconPrint':
                    $class = 'fas fa-print';
                    break;
                case 'iconSearch':
                    $class = 'fas fa-magnifying-glass';
                    break;
                case 'iconLayertree':
                    $class = 'fas fa-sitemap';
                    break;
                case 'iconWms':
                    $class = 'fas fa-globe';
                    break;
                case 'iconHelp':
                    $class = 'fas fa-circle-question';
                    break;
                case 'iconEdit':
                case 'iconWmcEditor':
                case 'iconSketch':
                    $class = 'fas fa-pen-to-square';
                    break;
                case 'iconWmcLoader':
                    $class = 'fas fa-folder-open';
                    break;
                case 'iconCoordinates':
                case 'iconGpsTarget':
                    $class = 'fas fa-crosshairs';
                    break;
                case 'iconPoi':
                    $class = 'fas fa-thumbtack';
                    break;
                case 'iconImageExport':
                    $class = 'fas fa-camera';
                    break;
                case 'iconCopyright':
                    $class = 'fas fa-copyright';
                    break;
                case 'iconShare':
                    $class = 'fas fa-share-nodes';
                    break;
                case 'iconShareArrow':
                    $class = 'fas fa-share';
                    break;
                case 'iconShareSquare':
                    $class = 'fas fa-share-from-square';
                    break;
                case 'iconRefresh':
                    $class = 'fas fa-rotate';
                    break;
                case 'iconMap':
                    $class = 'far fa-map';
                    break;
                case 'iconImages':
                    $class = 'far fa-images';
                    break;
                case 'iconMapPin':
                    $class = 'fas fa-map-pin';
                    break;
                case 'iconEarth':
                    $class = 'fas fa-earth-africa';
                    break;
                case 'iconDataUpload':
                    $class = 'fas fa-upload';
                    break;
                case 'iconRouting':
                    $class = 'fa-solid fa-route';
                    break;
                case 'iconBookmark':
                    $class = 'fa-regular fa-bookmark';
                    break;
                case 'iconChartColumn':
                    $class = 'fa-solid fa-chart-column';
                    break;
                case 'iconBookOpen':
                    $class = 'fa-solid fa-book-open';
                    break;
                case 'iconFolderTree':
                    $class = 'fa-solid fa-folder-tree';
                    break;
                case 'iconMapWithPin':
                    $class = 'fa-solid fa-map-location-dot';
                    break;
                case 'iconList':
                    $class = 'fa-solid fa-list';
                    break;
                case 'iconSettings':
                    $class = 'fa-solid fa-sliders';
                    break;
                case 'iconLightbulb':
                    $class = 'fa-regular fa-lightbulb';
                    break;
                case 'iconBarsStaggered':
                    $class = 'fa-solid fa-bars-staggered';
                    break;
                case 'iconCircleCheck':
                    $class = 'fa-regular fa-circle-check';
                    break;
                case 'iconCube':
                    $class = 'fa-solid fa-cube';
                    break;
                case 'iconCubes':
                    $class = 'fa-solid fa-cubes';
                    break;
                case 'iconEnvelope':
                    $class = 'fa-solid fa-envelope';
                    break;

            }
        }

        return HtmlUtil::renderTag('i', '', [
            'class' => $class . ' ' . $additionalClass,
        ]);
    }

    public function isHandled($iconCode)
    {
        foreach ($this->additionalIcons as $icon) {
            if ($icon['title'] === $iconCode) {
                return true;
            }
        }

        return \in_array($iconCode, $this->getChoices(true)) || \array_key_exists($iconCode, $this->getAliases());
    }

    public function getAliases(): array
    {
        return [
            'iconWmcEditor' => 'iconEdit',
            'iconSketch' => 'iconEdit',
            'iconGpsTarget' => 'iconCoordinates',
            'iconReset' => 'iconRefresh',
        ];
    }
}
