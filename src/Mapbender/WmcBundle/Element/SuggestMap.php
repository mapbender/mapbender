<?php
namespace Mapbender\WmcBundle\Element;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SuggestMap extends WmcBase
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wmc.suggestmap.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wmc.suggestmap.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wmc.suggestmap.suggest", "mb.wmc.suggestmap.map");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
            "receiver" => array('email'),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmcBundle\Element\Type\SuggestMapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmcBundle:ElementAdmin:suggestmap.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSuggestMap';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderWmcBundle/Resources/public/mapbender.element.suggestmap.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.social_media_connector.js',
            ),
            'css' => array(
                '@MapbenderWmcBundle/Resources/public/sass/element/suggestmap.scss',
            ),
            'trans' => array(
                'MapbenderWmcBundle:Element:suggestmap.json.twig',
                'MapbenderWmcBundle:Element:wmchandler.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $stateid = $this->container->get('request_stack')->getCurrentRequest()->get('stateid');
        if ($stateid) {
            $configuration["load"] = array('stateid' => $stateid);
        }
        return $configuration;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderWmcBundle:Element:suggestmap.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->getConfiguration();
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle(),
        ));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->getSession()->get("proxyAllowed", false) !== true) {
            throw new AccessDeniedHttpException('You are not allowed to use this proxy without a session.');
        }
        switch ($action) {
            case 'load':
                $id = $request->get("_id", null);
                return $this->loadState($id);
                break;
            case 'state':
                return $this->saveState();
                break;
            case 'content':
                return $this->getContent();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    protected function getContent()
    {
        $config = $this->getConfiguration();
        $html = $this->container->get('templating')
            ->render('MapbenderWmcBundle:Element:suggestmap-content.html.twig',
            array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle(),
        ));
        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }

    /**
     * Returns a json encoded state
     *
     * @param string $stateid
     * @return JsonResponse
     */
    protected function loadState($stateid)
    {
        $wmchandler = $this->wmcHandlerFactory();
        $state = $wmchandler->findState($stateid);
        if ($state) {
            $id = $state->getId();
            return new JsonResponse(array(
                "data" => array(
                    $id => json_decode($state->getJson()),
                ),
            ));
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.statenotfound", array(
                    '%stateid%' => $stateid,
                )),
            ));
        }
    }

    /**
     * Saves the mapbender state.
     *
     * @return JsonResponse
     */
    protected function saveState()
    {
        $wmchandler = $this->wmcHandlerFactory();
        $json = $this->container->get('request_stack')->getCurrentRequest()->get("state", null);
        $state = $wmchandler->saveState($json);
        if ($state !== null) {
            return new JsonResponse(array(
                "id" => $state->getId(),
            ));
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.statecannotbesaved"),
            ));
        }
    }
}
