<?php

namespace Mapbender\CoreBundle\Element;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;

class ApplicationSwitcher extends AbstractElementService
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var ApplicationYAMLMapper */
    protected $yamlAppRepository;
    /** @var AuthorizationCheckerInterface */
    protected $authChecker;

    public function __construct(FormFactoryInterface $formFactory,
                                ManagerRegistry $managerRegistry,
                                ApplicationYAMLMapper $yamlAppRepository,
                                AuthorizationCheckerInterface $authChecker)
    {
        $this->formFactory = $formFactory;
        $this->managerRegistry = $managerRegistry;
        $this->yamlAppRepository = $yamlAppRepository;
        $this->authChecker = $authChecker;
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
        $view->variables['form'] = $this->buildChoiceForm($element)->createView();
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        $dbRepository = $this->managerRegistry->getRepository(Application::class);
        $currentApplication = $element->getApplication();
        $applications = [];
        // Always offer the Application this Element is part of
        $applications[$currentApplication->getTitle()] = $currentApplication->getSlug();
        $config = $element->getConfiguration() ?: [];
        foreach ($config['applications'] as $slug) {
            if (\in_array($slug, $applications)) {
                continue;
            }
            $application = $this->yamlAppRepository->getApplication($slug);
            if (!$application) {
                $application = $dbRepository->findOneBy([
                    'slug' => $slug,
                ]);
            }
            if ($application && $this->authChecker->isGranted(ResourceDomainApplication::ACTION_VIEW, $application)) {
                $applications[$application->getTitle()] = $application->getSlug();
            }
        }
        $config['applications'] = $applications;
        return $config;
    }

    protected function buildChoiceForm(Element $element)
    {
        $current = $element->getApplication()->getSlug();
        $config = $this->getClientConfiguration($element);
        $options = array(
            'choices' => $config['applications'],
            'attr' => array(
                'title' => $element->getTitle(),
            ),
        );
        return $this->formFactory->createNamed('application', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $current, $options);
    }
}
