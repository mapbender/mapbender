<?php
namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\StateHandler;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\CoreBundle\Form\Type\StateType;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WmcHandler
{
    public static $WMC_DIR = "wmc";
    protected $element;
    protected $container;
    protected $application;

    /**
     * Creates a wmc handler
     * 
     * @param Element $element
     */
    public function __construct(Element $element, $application, $container)
    {
        $this->element = $element;
        $this->application = $application;
        $this->container = $container;
    }

    /**
     * Returns a state from a state id
     * 
     * @return Mapbender\CoreBundle\Entity\State or null.
     */
    public function findState($stateid)
    {
        $state = null;
        if ($stateid) {
            $state = $this->container->get('doctrine')
                ->getRepository('Mapbender\CoreBundle\Entity\State')
                ->find($stateid);
        }
        return $state;
    }

    /**
     * Saves and returns a saved state
     * 
     * @param array $jsonState a mapbender state
     * @return \Mapbender\CoreBundle\Entity\State or null
     */
    public function saveState($jsonState)
    {
        $state = null;
        if ($jsonState !== null) {
            $state = new State();
            $state->setServerurl($this->getBaseUrl());
            $state->setSlug($this->application->getSlug());
            $state->setTitle("SuggestMap");
            $state->setJson($jsonState);
            $em = $this->container->get('doctrine')->getEntityManager();
            $em->persist($state);
            $em->flush();
        }
        return $state;
    }

    /**
     * Returns a wmc.
     * @param integer $wmcid a Wmc id
     * 
     * @return Wmc or null.
     */
    public function getWmc($wmcid, $onlyPublic = TRUE)
    {
        $query = $this->container->get('doctrine')->getEntityManager()
            ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
                . " JOIN wmc.state s Where"
//		. " s.slug IN (:slug) AND"
                . " wmc.id=:wmcid"
                . ($onlyPublic === TRUE ? " AND wmc.public = 'true'" : "")
                . " ORDER BY wmc.id ASC")
//	    ->setParameter('slug', array($this->application->getSlug()))
            ->setParameter('wmcid', $wmcid);
        $wmc = $query->getResult();
        if ($wmc && count($wmc) === 1) return $wmc[0];
        else return null;
    }

    /**
     * Returns a wmc list
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getWmcList($onlyPublic = true)
    {
        $query = $this->container->get('doctrine')->getEntityManager()
            ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
                . " JOIN wmc.state s Where s.slug IN (:slug)"
                . ($onlyPublic === TRUE ? " AND wmc.public='true'" : "")
                . " ORDER BY wmc.id ASC")
            ->setParameter('slug', array($this->application->getSlug()));
        return $query->getResult();
    }

    /**
     * Gets a base url
     * 
     * @return string a base url
     */
    public function getBaseUrl()
    {
        $request = $this->container->get('request');
        $url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        return $url_base;
    }

    /**
     * Gets a url to wmc directory or to file with "$filename
     * 
     * @param string $filename
     * @return string a url to wmc directory or to file with "$filename" 
     */
    public function getWmcUrl($filename = null)
    {
        $url_base = $this->getBaseUrl() . '/'
            . $this->container->getParameter("mapbender.uploads_dir")
            . "/" . $this->application->getSlug() . '/' . WmcHandler::$WMC_DIR;
        ;
        if ($filename !== null) {
            return $url_base . '/' . $filename;
        } else {
            return $url_base;
        }
    }

    /**
     * Gets a path to wmc directory
     * 
     * @return string|null path to wmc directory or null
     */
    public function getWmcDir()
    {
        $wmc_dir = $this->container->get('kernel')->getRootDir() . '/../web/'
            . $this->container->getParameter("mapbender.uploads_dir")
            . "/" . $this->application->getSlug() . '/' . WmcHandler::$WMC_DIR;
        if (!is_dir($wmc_dir)) {
            if (mkdir($wmc_dir)) {
                return $wmc_dir;
            } else {
                return null;
            }
        } else {
            return $wmc_dir;
        }
    }

}
