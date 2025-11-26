<?php

namespace Mapbender\CoreBundle\Element;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Component\Application\DbAndYamlApplicationResolver;

class ApplicationSwitcher extends AbstractElementService implements ConfigMigrationInterface
{
    public function __construct(protected DbAndYamlApplicationResolver $applicationResolver,
                                protected AuthorizationCheckerInterface $authChecker,
                                protected UrlGeneratorInterface $router,
                                protected string $rootDir)
    {

    }

    public static function getClassTitle()
    {
        return 'mb.core.applicationSwitcher.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.applicationSwitcher.class.description';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'open_in_new_tab' => false,
            'applications' => array(),
        );
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ApplicationSwitcherAdminType';
    }

    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/applicationswitcher.html.twig';
    }

    public function getWidgetName(Element $element)
    {
        return 'MbApplicationSwitcher';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbApplicationSwitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/mbApplicationSwitcher.scss',
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/application_switcher.html.twig');
        $view->attributes['class'] = 'mb-element-applicationswitcher';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['config'] = $this->getClientConfiguration($element);
        $view->variables['region'] = $element->getRegion();
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        $config = $element->getConfiguration() ?: [];
        $config['applications'] = $this->prepareAppConfigurations($config['applications']);
        return $config;
    }

    public function prepareAppConfigurations($appConfigurations)
    {
        $preparedAppConfig = [];
        foreach ($appConfigurations as $slug => $appConfig) {
            $group = (!empty($appConfig['group'])) ? $appConfig['group'] : '__nogroup__';
            if (!empty($preparedAppConfig[$group]) && array_key_exists($slug, $preparedAppConfig[$group])) {
                continue;
            }
            try {
                $application = $this->applicationResolver->getApplicationEntity($slug);
                $appConfig['title'] = (!empty($appConfig['title'])) ? $appConfig['title'] : $application->getTitle();
                if (empty($appConfig['url'])) {
                    $appConfig['url'] = $this->router->generate('mapbender_core_application_application', ['slug' => $slug]);
                }
                if (empty($appConfig['imgUrl'])) {
                    $appConfig['imgUrl'] = false;
                    $imgPath = '/uploads/' . $slug . '/' . $application->getScreenshot();
                    if (@\is_file($this->rootDir . '/public' . $imgPath)) {
                        $appConfig['imgUrl'] = $this->router->getContext()->getBaseUrl() . $imgPath;
                    }
                }
                $preparedAppConfig[$group][$slug] = $appConfig;
            } catch (AccessDeniedException | NotFoundHttpException $e) { // external app (neither yaml nor database app)
                $preparedAppConfig[$group][$slug] = $appConfig;
            }
        }
        return $preparedAppConfig;
    }

    public static function updateEntityConfig(Element $entity)
    {
        $conf = $entity->getConfiguration();
        if (!empty($conf['applications'][0]) && is_string($conf['applications'][0])) {
            $appConfig = [];
            foreach ($conf['applications'] as $slug) {
                $appConfig[$slug] = [
                    'title' => null,
                    'url' => null,
                    'imgUrl' => null,
                    'group' => null,
                ];
            }
            $conf['applications'] = $appConfig;
        }
        $entity->setConfiguration($conf);
    }
}
