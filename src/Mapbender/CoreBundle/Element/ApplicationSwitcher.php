<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Kernel;

class ApplicationSwitcher extends Element
{
    public static function getClassTitle()
    {
        // @todo: translation
        return 'Application switcher';
    }

    public static function getClassDescription()
    {
        // @todo: translation
        return 'Switches to another application while maintaining current map position';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'open_in_new_tab' => true,
            'applications' => array(),
        );
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
            'choices' => $choices,
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
}
