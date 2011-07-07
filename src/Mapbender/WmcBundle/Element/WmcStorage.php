<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\WmcBundle\Entity\Wmc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class WmcStorage extends Element implements ElementInterface {
    public function getTitle() {
        return "WMC Storage Interface";
    }

    public function getDescription() {
        return "Stores and loads WMC documents. Can provide a dialog for "
            + "selecting and saving.";
    }

    public function getTags() {
        return array('wmc');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.wmcstorage.js',
            ),
            'css' => array()
        );
    }

    public function getConfiguration() {
        $opts = $this->configuration;
        if(array_key_exists('target', $this->configuration)) {
            $elementId = $this->configuration['target'];
            $finalId = $this->application->getFinalId($elementId);
            $opts = array_merge($opts, array('target' => $finalId));
        }
        return array(
            'options' => $opts,
            'init' => 'mbWmcStorage'
        );
    }

    public function httpAction($action) {
        $response = new Response();
        $request = $this->get('request');
        $em = $this->get('doctrine')->getEntityManager();
        $repository = $this->get('doctrine')->getRepository('MapbenderWmcBundle:Wmc');
        //TODO: owner shall be a reference to a UserInterface
        $owner = $this->get('security.context')->getToken()->getUser()->getUsername();

        switch($action) {
        case 'save':
            $title = $request->get('title');
            $public = strtolower($request->get('public'));
            $public = $public === 'true' ? true : false;
            if(!$title) {
                throw new \Exception('You did not send a title for the WMC document.');
            }

            $wmcDocument = file_get_contents('php://input');

            $wmc = $repository->findOneBy(array(
                'title' => $title,
                'owner' => $owner
            ));

            $status = array(
                'code' => 'ok'
            );

            if($wmc) {
            } else {
                // Create new entity, persist, say thank you
                $wmc = new Wmc();
                $wmc->setTitle($title);
                $wmc->setOwner($owner);
                $wmc->setPublic($public);
                $wmc->setDocument($wmcDocument);

                try {
                    $em->persist($wmc);
                    $em->flush();
                    $status['message'] = sprintf('Your WMC document was stored with id %d.', $wmc->getId());
                } catch (\Exception $e) {
                    $status['code'] = 'error';
                    $status['message'] = $e->getMessage();
                }
            }
            $response->setContent(json_encode($status));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
            break;

        case 'list':
            $query = $em->createQuery('SELECT w.id, w.title FROM MapbenderWmcBundle:Wmc w '
                . 'WHERE w.public = true OR w.owner = :owner')
                ->setParameter('owner', $owner);
            $response->setContent(json_encode($query->getResult()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
            break;

        case 'load':
            $params = $request->get('params');
            if(!is_array($params)) {
                throw new \Exception('The params parameter must be an array.');
            }

            $params = array_merge($params, array('owner' => $owner));

            $wmc = $repository->findOneBy($params);
            if(!$wmc) {
                throw new \Exception('No WMC found for your paramters.');
            }
            $response->setContent($wmc->getDocument());
            $response->headers->set('Content-Type', 'application/xml');
            return $response;
            break;
        }
        return parent::httpAction($action);
    }

    public function render() {
            return $this->get('templating')->render('MapbenderWmcBundle:Element:wmcstorage.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration));
    }
}

