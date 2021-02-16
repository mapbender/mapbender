<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

class ApplicationSwitcher extends Element
{
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

    public function getWidgetName()
    {
        return 'mapbender.mbApplicationSwitcher';
    }

    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbApplicationSwitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbApplicationSwitcher.css',
            ),
        );
    }

    public function getFrontendTemplatePath()
    {
        return 'MapbenderCoreBundle:Element:application_switcher.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->container->get('form.factory');
        $choices = array();
        $config = $this->entity->getConfiguration();
        $current = $this->entity->getApplication()->getSlug();
        foreach (ArrayUtil::getDefault($config, 'applications', array()) as $slug) {
            // @todo: use titles as labels
            $choices[$slug] = $slug;
        }
        // Current Application must be part of the selection
        if (!\in_array($current, $choices)) {
            $choices[$current] = $current;
        }
        $options = array(
            'choices' => $this->getApplicationChoices($this->entity),
            'attr' => array(
                'title' => $this->entity->getTitle(),
            ),
        );
        if (Kernel::MAJOR_VERSION < 3) {
            $options['choices_as_values'] = true;
        }
        $form = $formFactory->createNamed('application', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $current, $options);
        return array(
            'form' => $form->createView(),
        );
    }

    public function handleHttpRequest(Request $request)
    {
        /** @var ApplicationSwitcherHttpHandler $handler */
        $handler = $this->container->get('mb.element.application_switcher.http_handler');
        return $handler->handleHttpRequest($this->entity, $request);
    }

    protected function getApplicationChoices(Entity\Element $element)
    {
        // @todo: provide a combined yaml+db repository for Application entities
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $dbRepository = $em->getRepository('Mapbender\CoreBundle\Entity\Application');
        /** @var ApplicationYAMLMapper $yamlRepository */
        $yamlRepository = $this->container->get('mapbender.application.yaml_entity_repository');

        $currentApplication = $element->getApplication();
        $choices = array();

        // Always offer the Application this Element is part of
        $choices[$currentApplication->getTitle()] = $currentApplication->getSlug();

        $slugsConfigured = ArrayUtil::getDefault($element->getConfiguration(), 'applications', array());

        foreach ($slugsConfigured as $slug) {
            if (\in_array($slug, $choices)) {
                continue;
            }
            $application = $yamlRepository->getApplication($slug);
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
