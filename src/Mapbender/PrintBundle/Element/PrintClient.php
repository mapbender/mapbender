<?php

namespace Mapbender\PrintBundle\Element;

use Doctrine\Common\Collections\Collection;
use FOM\UserBundle\Entity\Group;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\OdgParser;
use Mapbender\PrintBundle\Component\Plugin\PrintQueuePlugin;
use Mapbender\PrintBundle\Component\Service\PrintServiceBridge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *
 */
class PrintClient extends Element
{

    public static $merge_configurations = false;

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
    public static function getClassTags()
    {
        return array(
            "mb.core.printclient.tag.print",
            "mb.core.printclient.tag.pdf",
            "mb.core.printclient.tag.png",
            "mb.core.printclient.tag.gif",
            "mb.core.printclient.tag.jpg",
            "mb.core.printclient.tag.jpeg");
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderPrintBundle/Resources/public/mapbender.element.imageExport.js',
                '@MapbenderPrintBundle/Resources/public/element/printclient.job-list.js',
                '@MapbenderPrintBundle/Resources/public/element/printclient.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
            ),
            'css' => array(
                '@MapbenderPrintBundle/Resources/public/element/printclient.scss',
            ),
            'trans' => array(
                'MapbenderPrintBundle:Element:printclient.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "templates" => array(
                array(
                    'template' => "a4portrait",
                    "label" => "A4 Portrait")
                ,
                array(
                    'template' => "a4landscape",
                    "label" => "A4 Landscape")
                ,
                array(
                    'template' => "a3portrait",
                    "label" => "A3 Portrait")
                ,
                array(
                    'template' => "a3landscape",
                    "label" => "A3 Landscape")
                ,
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical"),
                array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical")
            ),
            "scales" => array(500, 1000, 5000, 10000, 25000),
            "quality_levels" => array(array('dpi' => "72", 'label' => "Draft (72dpi)"),
                array('dpi' => "288", 'label' => "Document (288dpi)")),
            "rotatable" => true,
            "legend" => true,
            "legend_default_behaviour" => true,
            "optional_fields" => array(
                "title" => array("label" => 'Title', "options" => array("required" => false)),
                "comment1" => array("label" => 'Comment 1', "options" => array("required" => false)),
                "comment2" => array("label" => 'Comment 2', "options" => array("required" => false))),
            'required_fields_first' => false,
            "replace_pattern" => null,
            "file_prefix" => 'mapbender'
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'mapbender.form_type.element.printclient';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderPrintBundle:ElementAdmin:printclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPrintClient';
    }

    public function getPublicConfiguration()
    {
        return $this->entity->getConfiguration() + array(
            'type' => 'dialog',
            // NOTE: intl extension locale is runtime-controlled by Symfony to reflect framework configuration
            'locale' => \locale_get_default(),
        );
    }

    /**
     * @return string
     */
    protected function getSubmitAction()
    {
        if ($this->isQueueModeEnabled()) {
            return PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE;
        } else {
            return 'print';
        }
    }

    public function getFrontendTemplateVars()
    {
        $config = array_filter($this->entity->getConfiguration()) + array(
            'required_fields_first' => false,
            'type' => 'dialog',
        );
        $router = $this->container->get('router');
        $submitUrl = $router->generate('mapbender_core_application_element', array(
            'slug' => $this->entity->getApplication()->getSlug(),
            'id' => $this->entity->getId(),
            'action' => $this->getSubmitAction(),
        ));
        $vars = array(
            'configuration' => $config,
            'submitUrl' => $submitUrl,
            'settingsTemplate' => $this->getSettingsTemplate(),
        );
        if ($this->isQueueModeEnabled()) {
            $submitFrameName = $this->getSubmitFrameName();
            return $vars + array(
                'formTarget' => $submitFrameName,
                'submitFrameName' => $submitFrameName,
            );
        } else {
            return $vars + array(
                'formTarget' => '_blank',
            );
        }
    }

    protected function getSettingsTemplate()
    {
        return 'MapbenderPrintBundle:Element:printclient-settings.html.twig';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        if ($this->isQueueModeEnabled()) {
            return "MapbenderPrintBundle:Element:printclient-queued.html.twig";
        } else {
            return "MapbenderPrintBundle:Element:printclient.html.twig";
        }
    }

    /**
     * @return string
     */
    protected function generateFilename()
    {
        $configuration = $this->entity->getConfiguration();
        $prefix = ArrayUtil::getDefault($configuration, 'file_prefix', null);
        $prefix = $prefix ?: ArrayUtil::getDefault($this->getDefaultConfiguration(), 'file_prefix', null);
        $prefix = $prefix ?: 'mapbender_print';
        return $prefix . '_' . date("YmdHis") . '.pdf';
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var Request $request */
        $request = $this->container->get('request');
        $bridgeService = $this->getServiceBridge();
        $configuration = $this->entity->getConfiguration();
        switch ($action) {
            case 'print':
                $rawData = $this->extractRequestData($request);
                $jobData = $this->preparePrintData($rawData, $configuration);

                $pdfBody = $bridgeService->dumpPrint($jobData);

                $displayInline = true;
                $filename = $this->generateFilename();

                $response = new Response($pdfBody, 200, array(
                    'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename=' . $filename
                ));

                return $response;

            case 'getTemplateSize':
                $template = $request->get('template');
                /** @var OdgParser $odgParser */
                $odgParser = $this->container->get('mapbender.print.template_parser.service');
                $size = $odgParser->getMapSize($template);

                return new Response($size);

            default:
                $response = $bridgeService->handleHttpRequest($request, $this->entity);
                if ($response) {
                    return $response;
                }
                $queuePlugin = $this->getActiveQueuePlugin();
                if ($queuePlugin && $action === PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE) {
                    $rawData = $this->extractRequestData($request);
                    $jobData = $this->preparePrintData($rawData, $configuration);
                    $queuePlugin->putJob($jobData, $this->generateFilename());
                    return new Response('', 204);
                }
                throw new NotFoundHttpException();
        }
    }

    /**
     * @return PrintServiceBridge
     */
    protected function getServiceBridge()
    {
        /** @var PrintServiceBridge $bridgeService */
        $bridgeService = $this->container->get('mapbender.print_service_bridge.service');
        return $bridgeService;
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
        $urlProcessor = $this->getUrlProcessor();
        foreach ($data['layers'] as $ix => $layerDef) {
            if (!empty($layerDef['url'])) {
                $updatedUrl = $urlProcessor->getInternalUrl($layerDef['url']);
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
            $urlProcessor = $this->getUrlProcessor();
            foreach ($overviewDef['layers'] as $index => $url) {
                $overviewDef['layers'][$index] = $urlProcessor->getInternalUrl($url);
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
            if (isset($pattern['default'][$dpi])) {
                return $url . $pattern['default'][$dpi];
            } elseif (strpos($url, $pattern['pattern']) !== false) {
                if (isset($pattern['replacement'][$dpi])){
                    return str_replace($pattern['pattern'], $pattern['replacement'][$dpi], $url);
                }
            }
        }
        // no match, no change
        return $url;
    }

    /**
     * @param array[] $legendDefs
     * @return string[]
     */
    protected function prepareLegends($legendDefs)
    {
        $urlProcessor = $this->getUrlProcessor();
        $legendDefsOut = array();
        foreach ($legendDefs as $ix => $imageList) {
            $legendDefsOut[$ix] = array();
            foreach ($imageList as $title => $legendImageUrl) {
                $internalUrl = $urlProcessor->getInternalUrl($legendImageUrl);
                $legendDefsOut[$ix][$title] = $internalUrl;
            }
        };
        return $legendDefsOut;
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
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if ($token && !$token instanceof AnonymousToken) {
            $user = $token->getUser();
            // getUser's return value can be ...
            if ($user instanceof UserInterface) {
                // a) a UserInterface
                $values = array_replace($values, array(
                    'userId' => $user->getId(),
                    'userName' => $user->getUsername(),
                ));
            } elseif ($user) {
                // b) an object with a __toString method or just a string
                $values['userName'] = "{$user}";
            }

            try {
                // This only works for FOM user entity; getGroups is not part of
                // the framework's base UserInterface.
                if ($user instanceof \FOM\UserBundle\Entity\User) {
                    /** @var Collection|Group[] $groups */
                    $groups = $user->getGroups();
                } else {
                    $groups = null;
                }
                if ($groups && count($groups)) {
                    /** @var Collection|Group[] $groups */
                    $values = array_replace($values, array(
                        'legendpage_image' => array(
                            'type' => 'resource',
                            'path' => 'images/' . $groups[0]->getTitle() . '.png',
                        ),
                        'dynamic_image' => array(
                            'type' => 'resource',
                            'path' => 'images/' . $groups[0]->getTitle() . '.png',
                        ),
                        'dynamic_text' => array(
                            'type' => 'text',
                            'text' => $groups[0]->getDescription(),
                        ),
                    ));
                }

            } catch (\Exception $e) {
                // wrong user entity type, nothing we can do (fall through to default)
            }
        }
        return $values;
    }

    /**
     * Returns the queue plugin service ONLY IF
     * 1) enabled by global container parameter
     * and
     * 2) registerd in the plugin host
     * and
     * 3) queued job processing is enabled by current Element configuration
     *
     * @return PrintQueuePlugin|null
     */
    protected function getActiveQueuePlugin()
    {
        if (!$this->container->getParameter('mapbender.print.queueable')) {
            return null;
        }
        $config = $this->entity->getConfiguration();
        if (empty($config['renderMode']) || $config['renderMode'] != 'queued') {
            return null;
        }
        /** @var PrintQueuePlugin|null $queuePlugin */
        $queuePlugin = $this->getServiceBridge()->getPluginHost()->getPlugin('print-queue');
        return $queuePlugin;
    }

    /**
     * @return bool
     */
    protected function isQueueModeEnabled()
    {
        return !!$this->getActiveQueuePlugin();
    }

    /**
     * @return UrlProcessor
     */
    protected function getUrlProcessor()
    {
        /** @var UrlProcessor $service */
        $service = $this->container->get('mapbender.source.url_processor.service');
        return $service;
    }

    /**
     * Generates an iframe name that can be used for ~"invisible" form submission, Ajax posts etc.
     * @todo: this would be more convenient to have on the template level, so all Elements could share a single
     *        frame.
     */
    protected function getSubmitFrameName()
    {
        return "submit-frame-{$this->entity->getId()}";
    }
}
