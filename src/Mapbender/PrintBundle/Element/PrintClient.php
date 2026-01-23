<?php

namespace Mapbender\PrintBundle\Element;

use Doctrine\Common\Collections\Collection;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Component\OdgParser;
use Mapbender\PrintBundle\Component\Plugin\PrintQueuePlugin;
use Mapbender\PrintBundle\Component\Service\PrintPluginHost;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\Utils\MemoryUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *
 */
class PrintClient extends AbstractElementService implements ConfigMigrationInterface, ElementHttpHandlerInterface
{
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var UrlProcessor */
    protected $sourceUrlProcessor;
    /** @var OdgParser */
    protected $odgParser;
    /** @var PrintServiceInterface */
    protected $printService;
    /** @var PrintPluginHost */
    protected $pluginRegistry;
    /** @var string|null */
    protected $memoryLimit;
    /** @var boolean */
    protected $enableQueue;

    public function __construct(UrlGeneratorInterface $urlGenerator,
                                FormFactoryInterface  $formFactory,
                                TokenStorageInterface $tokenStorage,
                                UrlProcessor          $sourceUrlProcessor,
                                OdgParser             $odgParser,
        /** @todo: elminate bridge service */
                                PrintServiceInterface $printService,
                                PrintPluginHost       $pluginRegistry,
                                                      $memoryLimit,
                                                      $enableQueue)
    {
        $this->urlGenerator = $urlGenerator;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
        $this->sourceUrlProcessor = $sourceUrlProcessor;
        $this->odgParser = $odgParser;
        $this->printService = $printService;
        $this->pluginRegistry = $pluginRegistry;
        $this->memoryLimit = $memoryLimit;
        $this->enableQueue = $enableQueue;
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.printclient.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.printclient.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/ol.interaction.Transform.js',
                '@MapbenderPrintBundle/Resources/public/MbImageExport.js',
                '@MapbenderPrintBundle/Resources/public/element/MbPrintJobList.js',
                '@MapbenderPrintBundle/Resources/public/element/MbPrint.js',
            ),
            'css' => array(
                '@MapbenderPrintBundle/Resources/public/sass/element/printclient.scss',
            ),
            'trans' => array(
                'mb.core.printclient.btn.*',
                'mb.print.printclient.joblist.*',
                'mb.print.imageexport.info.*',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "templates" => array(
                array(
                    'template' => "a4portrait",
                    "label" => "A4 Portrait",
                ),
                array(
                    'template' => "a4landscape",
                    "label" => "A4 Landscape",
                ),
                array(
                    'template' => "a3portrait",
                    "label" => "A3 Portrait",
                ),
                array(
                    'template' => "a3landscape",
                    "label" => "A3 Landscape",
                ),
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical",
                ),
                array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical",
                ),
            ),
            "scales" => array(
                500,
                1000,
                5000,
                10000,
                25000,
            ),
            "quality_levels" => array(
                array(
                    'dpi' => "72",
                    'label' => "Draft (72dpi)",
                ),
                array(
                    'dpi' => "288",
                    'label' => "Document (288dpi)",
                ),
            ),
            "rotatable" => true,
            "legend" => true,
            "legend_default_behaviour" => true,
            "optional_fields" => array(
                "title" => array(
                    "label" => 'mb.core.printclient.class.title',
                    "options" => array(
                        "required" => false,
                    ),
                ),
            ),
            'required_fields_first' => false,
            "replace_pattern" => null,
            "file_prefix" => 'mapbender',
            'renderMode' => 'direct',
            'queueAccess' => 'global',
            'element_icon' => self::getDefaultIcon(),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\PrintBundle\Element\Type\PrintClientAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderPrint/ElementAdmin/printclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbPrint';
    }

    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration() + array(
                // NOTE: intl extension locale is runtime-controlled by Symfony to reflect framework configuration
                'locale' => \locale_get_default(),
            );
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();
        $queueMode = $this->enableQueue && !empty($config['renderMode']) && $config['renderMode'] === 'queued';

        if ($queueMode) {
            $template = '@MapbenderPrint/Element/printclient-queued.html.twig';
        } else {
            $template = '@MapbenderPrint/Element/printclient.html.twig';
        }
        $view = new TemplateView($template);
        $view->attributes['class'] = 'mb-element-printclient';
        $view->attributes['data-title'] = $element->getTitle();

        $submitUrl = $this->urlGenerator->generate('mapbender_core_application_element', array(
            'slug' => $element->getApplication()->getSlug(),
            'id' => $element->getId(),
            'action' => $queueMode ? PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE : 'print',
        ));
        $view->variables = array(
            'submitUrl' => $submitUrl,
            'settingsTemplate' => $this->getSettingsTemplate(),
            'settingsForm' => $this->getSettingsForm($element)->createView(),
            // for legacy custom templates only
            'id' => $element->getId(),
            'title' => $element->getTitle(),
            'configuration' => $config + array(
                    'required_fields_first' => false,
                    'type' => 'dialog',
                ),
        );
        if ($queueMode) {
            /**
             * Generate an iframe name that can be used for ~"invisible" form submission, Ajax posts etc.
             * @todo: this would be more convenient to have on the template level, so all Elements could share a single
             */
            $submitFrameName = "submit-frame-{$element->getId()}";
            $view->variables += array(
                'formTarget' => $submitFrameName,
                'submitFrameName' => $submitFrameName,
            );
        } else {
            $view->variables += array(
                'formTarget' => '_blank',
            );
        }
        return $view;
    }

    protected function getSettingsTemplate()
    {
        return '@MapbenderPrint/Element/printclient-settings.html.twig';
    }

    protected function getSettingsFormType()
    {
        return 'Mapbender\PrintBundle\Form\PrintClientSettingsType';
    }

    protected function getSettingsForm(Element $element)
    {
        $formType = $this->getSettingsFormType();
        $config = $element->getConfiguration();
        $options = array(
            'templates' => ArrayUtil::getDefault($config, 'templates', array()),
            'required_fields_first' => ArrayUtil::getDefault($config, 'required_fields_first', false),
            'custom_fields' => ArrayUtil::getDefault($config, 'optional_fields', array()) ?: array(),
            'quality_levels' => ArrayUtil::getDefault($config, 'quality_levels', array()),
            'scales' => ArrayUtil::getDefault($config, 'scales', array()),
            'show_rotation' => ArrayUtil::getDefault($config, 'rotatable', true),
            'show_printLegend' => ArrayUtil::getDefault($config, 'legend', true),
            'compound' => true,
        );
        $data = array(
            'rotation' => '0',
            'printLegend' => !!ArrayUtil::getDefault($config, 'legend_default_behaviour', true),
        );

        return $this->formFactory->createNamed('', $formType, $data, $options);
    }

    /**
     * @param Element $element
     * @return string
     */
    protected function generateFilename(Element $element)
    {
        $configuration = $element->getConfiguration();
        $prefix = ArrayUtil::getDefault($configuration, 'file_prefix', null);
        $prefix = $prefix ?: ArrayUtil::getDefault($this->getDefaultConfiguration(), 'file_prefix', null);
        $prefix = $prefix ?: 'mapbender_print';
        return $prefix . '_' . date("YmdHis") . '.pdf';
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        $configuration = $element->getConfiguration();
        switch ($action) {
            case 'print':
                $rawData = $this->extractRequestData($request);
                $jobData = $this->preparePrintData($rawData, $configuration);
                $jobData['application'] = $element->getApplication();

                $this->checkMemoryLimit();
                $pdfBody = $this->printService->dumpPrint($jobData);

                $displayInline = true;
                $filename = $this->generateFilename($element);

                $response = new Response($pdfBody, Response::HTTP_OK, array(
                    'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename=' . $filename
                ));

                return $response;

            case 'getTemplateSize':
                $template = $request->get('template');
                $size = $this->odgParser->getMapSize($template);

                return new Response($size);

            case PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE:
                if (!empty($configuration['renderMode']) && $configuration['renderMode'] === 'queued') {
                    $queuePlugin = $this->pluginRegistry->getPlugin('print-queue');
                    $rawData = $this->extractRequestData($request);
                    $jobData = $this->preparePrintData($rawData, $configuration);
                    $jobData['application'] = $element->getApplication()->getSlug();
                    $queuePlugin->putJob($jobData, $this->generateFilename($element));
                    return new Response('', Response::HTTP_NO_CONTENT);
                } else {
                    throw new NotFoundHttpException();
                }
            default:
                $response = $this->pluginRegistry->handleHttpRequest($request, $element);
                if ($response) {
                    return $response;
                } else {
                    throw new NotFoundHttpException();
                }
        }
    }

    /**
     * Extracts / decodes submitted values from request.
     * This is separated from preparePrintData for extensibility reasons.
     * Output should be a bare array without any remaining serialized (json or otherwise) data.
     * Output will get passed to preparePrintData as is.
     *
     * @param Request $request
     * @return array
     */
    protected function extractRequestData(Request $request)
    {
        // @todo: define what data we support; do not simply process and forward everything
        $data = $request->request->all();
        if (isset($data['data'])) {
            $d0 = $data['data'];
            unset($data['data']);
            $data = array_replace($data, json_decode($d0, true));
        }
        return $data;
    }

    /**
     * Preprocesses / amends job data so it can be safely executed by print service, but also
     * safely persisted to db for execution at a later time. I.e. information pertinent to
     * current user and current element configuration needs to be fully resolved.
     *
     * @param array $data
     * @param mixed[] $configuration
     * @return mixed[]
     */
    protected function preparePrintData($data, $configuration)
    {
        foreach ($data['layers'] as $ix => $layerDef) {
            if (!empty($layerDef['url'])) {
                $updatedUrl = $this->sourceUrlProcessor->getInternalUrl($layerDef['url']);
                if (!empty($configuration['replace_pattern'])) {
                    $updatedUrl = $this->addReplacePattern($updatedUrl, $configuration['replace_pattern'], $data['quality']);
                }
                $data['layers'][$ix]['url'] = $updatedUrl;
            }
        }

        if (isset($data['overview'])) {
            $data['overview'] = $this->prepareOverview($data['overview']);
        }

        if (isset($data['legends'])) {
            $data['legends'] = $this->prepareLegends($data['legends']);
        }
        $data = $data + $this->getUserSpecifics();
        return $data;
    }

    protected function prepareOverview($overviewDef)
    {
        if (!empty($overviewDef['layers'])) {
            foreach ($overviewDef['layers'] as $index => $layer) {
                if (isset($layer['url'])) {
                    $overviewDef['layers'][$index]['url'] = $this->sourceUrlProcessor->getInternalUrl($layer['url']);
                }
            }
        }
        return $overviewDef;
    }

    /**
     * Apply "replace_pattern" backend configuration to given $url, either
     * rewriting a part of it or appending something, depending on $dpi
     * value.
     *
     * @param string $url
     * @param array $rplConfig
     * @param int $dpi
     * @return string updated $url
     */
    protected function addReplacePattern($url, $rplConfig, $dpi)
    {
        foreach ($rplConfig as $pattern) {
            switch (key($pattern)) {
                case 'default':
                    $url = $this->addUrlPattern($url, $pattern, $dpi);
                    break;
                case 'pattern':
                    $url = $this->replaceUrlPattern($url, $pattern, $dpi);
                    break;
                default:
                    break;
            }
        }

        // no match, no change
        return $url;
    }

    protected function prepareLegends(array $legendDefs): array
    {
        for ($ix = 0; $ix < count($legendDefs); $ix++) {
            foreach ($legendDefs[$ix] as $imageListKey => $sourceLegendData) {
                if (is_string($sourceLegendData)) {
                    // Old style title => url mapping. May go out of order depending on browser's and PHP's
                    // JSON processing
                    $internalUrl = $this->sourceUrlProcessor->getInternalUrl($sourceLegendData);
                    $legendDefs[$ix][$imageListKey] = [
                        'url' => $internalUrl,
                        'type' => 'url',
                        'layerName' => $imageListKey,
                    ];
                } elseif (is_array($sourceLegendData) && array_key_exists('url', $sourceLegendData)) {
                    $internalUrl = $this->sourceUrlProcessor->getInternalUrl($sourceLegendData['url']);
                    $legendDefs[$ix][$imageListKey]['url'] = $internalUrl;

                    if (!array_key_exists('type', $sourceLegendData)) {
                        $legendDefs[$ix][$imageListKey]['type'] = 'url';
                    }
                }
            }
        }
        return $legendDefs;
    }

    /**
     * @return array
     */
    protected function getUserSpecifics()
    {
        // initialize safe defaults
        $values = array(
            'userId' => null,
            'userName' => null,
            'legendpage_image' => array(
                'type' => 'resource',
                'path' => 'images/legendpage_image.png',
            ),
        );
        $fomGroups = array();
        $token = $this->tokenStorage->getToken();
        if ($token && !$token instanceof NullToken) {
            $user = $token->getUser();
            // getUser's return value can be a lot of different things
            if (is_object($user) && ($user instanceof \FOM\UserBundle\Entity\User)) {
                $values = array_replace($values, array(
                    'userId' => $user->getId(),
                    'userName' => $user->getUserIdentifier(),
                ));
                $fomGroups = $user->getGroups() ?: array();
                if (is_object($fomGroups) && ($fomGroups instanceof Collection)) {
                    $fomGroups = $fomGroups->getValues();
                }
            } elseif (is_object($user) && ($user instanceof UserInterface)) {
                $values = array_replace($values, array(
                    'userName' => $user->getUserIdentifier(),
                ));
            } elseif ($user) {
                // b) an object with a __toString method or just a string
                $values['userName'] = "{$user}";
            }
            if ($fomGroups) {
                $firstGroup = $fomGroups[0];
                $values = array_replace($values, $this->getGroupSpecifics($firstGroup, $user));
            }
        }
        return $values;
    }

    /**
     * Extracts group-specific values. This implementation only works for FOM Group entities.
     * Other types are accepted, but you will always get an empty array for them.
     *
     * Unused param $user is provided for override methods, if you want to look into your
     * LDAP or something. This can have a multitude of types.
     * @param \FOM\UserBundle\Entity\Group|mixed $group
     * @param UserInterface|object|string $user
     * @return array
     * @see AbstractToken::setUser()
     *
     */
    protected function getGroupSpecifics($group, $user)
    {
        if (is_object($group) && ($group instanceof \FOM\UserBundle\Entity\Group)) {
            return array(
                'legendpage_image' => array(
                    'type' => 'resource',
                    'path' => 'images/' . $group->getTitle() . '.png',
                ),
                'dynamic_image' => array(
                    'type' => 'resource',
                    'path' => 'images/' . $group->getTitle() . '.png',
                ),
                'dynamic_text' => array(
                    'type' => 'text',
                    'text' => $group->getDescription(),
                ),
            );
        } else {
            return array();
        }
    }

    /**
     * @param $url
     * @param $pattern
     * @param $dpi
     * @return mixed
     */
    private function replaceUrlPattern($url, $pattern, $dpi)
    {
        if (!isset($pattern['replacement'][$dpi])) {
            return $url;
        }

        return str_replace($pattern['pattern'], $pattern['replacement'][$dpi], $url);
    }

    /**
     * @param $url
     * @param $pattern
     * @param $dpi
     * @return string
     */
    private function addUrlPattern($url, $pattern, $dpi)
    {
        if (!isset($pattern['default'][$dpi]))
            return $url;

        return $url . $pattern['default'][$dpi];
    }

    /**
     * Dynamically bumps the memory limit up to the configured value.
     */
    protected function checkMemoryLimit()
    {
        // ignore null values as documented
        if ($this->memoryLimit) {
            MemoryUtil::increaseMemoryLimit($this->memoryLimit);
        }
    }

    public static function updateEntityConfig(Element $entity)
    {
        $values = $entity->getConfiguration();
        if ($values && !empty($values['scales'])) {
            // Force all 'scales' values to integer
            $values['scales'] = array_map('intval', $values['scales']);
            // Remove (invalid) 0 / null / empty 'scales' values
            $values['scales'] = array_filter($values['scales']);
            $entity->setConfiguration($values);
        }
    }

    public static function getDefaultIcon()
    {
        return 'iconPrint';
    }
}
