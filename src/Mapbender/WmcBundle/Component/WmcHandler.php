<?php
namespace Mapbender\WmcBundle\Component;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmcBundle\Entity\Wmc;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmcHandler
{
    public static $WMC_DIR = "wmc";
    /** @var ContainerInterface */
    protected $container;
    /** @var Application */
    protected $application;
    /** @var EntityManager */
    protected $entityManager;

    /**
     * Creates a wmc handler
     *
     * @param Application $application
     * @param ContainerInterface $container
     */
    public function __construct($application, $container)
    {
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->application = $application;
        $this->container = $container;
    }

    /**
     * Returns a state from a state id
     *
     * @return State|null
     */
    public function findState($stateid)
    {
        $state = null;
        if ($stateid) {
            $state = $this->container->get('doctrine')
                ->getRepository('Mapbender\CoreBundle\Entity\State')
                ->find($stateid);
        }
        return $this->signUrls($state);
    }

    /**
     * Saves and returns a saved state
     *
     * @param string $jsonState
     * @return State|null
     */
    public function saveState($jsonState)
    {
        $state = null;
        if ($jsonState !== null) {
            $state = new State();
            $state->setSlug($this->application->getSlug());
            $state->setTitle("SuggestMap");
            $state->setJson($jsonState);
            $state = $this->unSignUrls($state);
            $this->entityManager->persist($state);
            $this->entityManager->flush();
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
        $query = $this->entityManager
            ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
                . " JOIN wmc.state s Where"
//		. " s.slug IN (:slug) AND"
                . " wmc.id=:wmcid"
                . ($onlyPublic === TRUE ? " AND wmc.public = :public" : "")
                . " ORDER BY wmc.id ASC")
//	    ->setParameter('slug', array($this->application->getSlug()))
            ->setParameter('wmcid', $wmcid);
        if($onlyPublic) $query->setParameter('public', true);
        $wmc = $query->getResult();
        if ($wmc && count($wmc) === 1) {
            $wmc_signed = $wmc[0];
            $wmc_signed->setState($this->signUrls($wmc_signed->getState()));
            return $wmc_signed;
        } else {
            return null;
        }
    }

    /**
     * Returns a wmc list
     *
     * @param bool $onlyPublic
     * @return Wmc[]
     */
    public function getWmcList($onlyPublic = true)
    {
        $query = $this->entityManager
            ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
                . " JOIN wmc.state s Where s.slug IN (:slug)"
                . ($onlyPublic === TRUE ? " AND wmc.public=:public" : "")
                . " ORDER BY wmc.id ASC")
            ->setParameter('slug', array($this->application->getSlug()));
        if($onlyPublic) $query->setParameter('public', true);
        return $query->getResult();
    }

    /**
     * Gets a url to wmc directory or to file with "$filename
     *
     * @param string $filename
     * @return string a url to wmc directory or to file with "$filename"
     */
    public function getWmcUrl($filename = null)
    {
        $url_base = \Mapbender\CoreBundle\Component\Application::getAppWebUrl($this->container, $this->application->getSlug());
        $url_wmc = $url_base . '/' . WmcHandler::$WMC_DIR;
        if ($filename !== null) {
            return $url_wmc . '/' . $filename;
        } else {
            return $url_wmc;
        }
    }

    /**
     * Gets a path to wmc directory
     *
     * @return string|null path to wmc directory or null
     */
    public function getWmcDir()
    {
        $uploads_dir = \Mapbender\CoreBundle\Component\Application::getAppWebDir($this->container, $this->application->getSlug());
        $wmc_dir = $uploads_dir . '/' . WmcHandler::$WMC_DIR;
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

    public function unSignUrls(State $state){
        $json = json_decode($state->getJson(), true);
        if ($json && isset($json['sources']) && is_array($json['sources'])) {
            foreach ($json['sources'] as &$source) {
                $url = UrlUtil::validateUrl($source['configuration']['options']['url'], array(), array('_signature'));
                $source['configuration']['options']['url'] = $url;
            }
        }
        $state->setJson(json_encode($json));
        return $state;
    }

    public function signUrls(State $state){
        $state->getId();
        $json = json_decode($state->getJson(), true);
        if($json && isset($json['sources']) && is_array($json['sources'])){
            $signer = $this->container->get('signer');
            foreach($json['sources'] as &$source){
                // strip _signature, but also '0' created by previously broken unSignUrls code
                $url = UrlUtil::validateUrl($source['configuration']['options']['url'], array(), array('_signature', '0'));
                $source['configuration']['options']['url'] = $signer->signUrl($url);
            }
        }
        $state->setJson(json_encode($json));
        return $state;
    }

}
