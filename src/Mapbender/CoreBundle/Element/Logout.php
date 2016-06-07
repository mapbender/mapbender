<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

class Logout extends Element
{
    /**
     * @return string
     */
    public static function getClassTitle()
    {
        return "Logout";
    }

    /**
     * @return string
     */
    public static function getClassDescription()
    {
        return "Logout Button!";
    }

    /**
     * @return array
     */
    public static function getClassTags()
    {
        return array("logout");
    }

    /**
     * @return array
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/js/mapbender.element.logout.js'
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/logout.scss'
            ),
            
        );
    }

    /**
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title'   => 'logout element',
            'label'   => 'Abmelden',
            'confirm' => 'Wirklich abmelden?'
        );
    }

    /**
     * @return string
     */
    public function getWidgetName()
    {
        return 'mapbender.mbLogout';
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this
            ->container
            ->get('templating')
            ->render(
                'MapbenderCoreBundle:Element:logout.html.twig',
                array(
                    'id'            => $this->getId(),
                    'title'         => $this->getTitle(),
                    'configuration' => $this->entity->getConfiguration()
                )
            );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LogoutAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:logout.html.twig';
    }
}
