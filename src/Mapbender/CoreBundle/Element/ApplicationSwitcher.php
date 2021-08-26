<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;


class ApplicationSwitcher extends AbstractElementService
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var ElementHttpHandlerInterface */
    protected $httpHandler;
    /** @var ApplicationYAMLMapper */
    protected $yamlAppRepository;

    public function __construct(FormFactoryInterface $formFactory,
                                ManagerRegistry $managerRegistry,
                                ElementHttpHandlerInterface $httpHandler,
                                ApplicationYAMLMapper $yamlAppRepository)
    {
        $this->formFactory = $formFactory;
        $this->managerRegistry = $managerRegistry;
        $this->httpHandler = $httpHandler;
        $this->yamlAppRepository = $yamlAppRepository;
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
        return null;
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbApplicationSwitcher';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbApplicationSwitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbApplicationSwitcher.scss',
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:application_switcher.html.twig');
        $view->attributes['class'] = 'mb-element-applicationswitcher';
        $view->variables['form'] = $this->buildChoiceForm($element)->createView();
        return $view;
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }

    protected function buildChoiceForm(Element $element)
    {
        $current = $element->getApplication()->getSlug();
        $options = array(
            'choices' => $this->getApplicationChoices($element),
            'attr' => array(
                'title' => $element->getTitle(),
            ),
        );
        return $this->formFactory->createNamed('application', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $current, $options);
    }

    protected function getApplicationChoices(Element $element)
    {
        // @todo: provide a combined yaml+db repository for Application entities
        $dbRepository = $this->managerRegistry->getRepository('Mapbender\CoreBundle\Entity\Application');

        $currentApplication = $element->getApplication();
        $choices = array();

        // Always offer the Application this Element is part of
        $choices[$currentApplication->getTitle()] = $currentApplication->getSlug();

        $slugsConfigured = ArrayUtil::getDefault($element->getConfiguration(), 'applications', array());

        foreach ($slugsConfigured as $slug) {
            if (\in_array($slug, $choices)) {
                continue;
            }
            $application = $this->yamlAppRepository->getApplication($slug);
            if (!$application) {
                $application = $dbRepository->findOneBy(array('slug' => $slug));
            }
            if ($application) {
                $choices[$application->getTitle()] = $application->getSlug();
            }
        }
        return $choices;
    }
}
