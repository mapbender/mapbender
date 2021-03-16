<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\MapViewDiff;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Templating\EngineInterface;

class ViewManagerHttpHandler
{
    /** @var EngineInterface */
    protected $templating;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(EngineInterface $templating, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->templating = $templating;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleHttpRequest(Entity\Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            default:
                throw new NotFoundHttpException();
            case 'listing':
                return $this->getListingResponse($element, $request);
            case 'save':
                return $this->getSaveResponse($element, $request);
            case 'delete':
                return $this->getDeleteResponse($element, $request);
        }
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     */
    protected function getListingResponse(Entity\Element $element, Request $request)
    {
        $config = $element->getConfiguration();
        $isAnon = $this->isCurrentUserAnonymous();
        $showPublic = !!$config['publicEntries'] || ($isAnon && $config['privateEntries']);
        $showPrivate = !!$config['privateEntries'] && !$isAnon;
        $criteria = array(
            'applicationSlug' => $element->getApplication()->getSlug(),
        );
        if ($showPublic && !$showPrivate) {
            $criteria['userId'] = null;
        } elseif ($showPrivate && !$showPublic) {
            $criteria['userId'] = $this->getUserId();
        }

        $records = $this->getRepository()->findBy($criteria);

        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing.html.twig', array(
            'records' => $records,
            'dateFormat' => $this->getDateFormat($request),
            'savePrivate' => $config['privateEntries'] === 'rw',
            'savePublic' => $config['publicEntries'] === 'rw',
        ));
        return new Response($content);
    }

    protected function getSaveResponse(Entity\Element $element, Request $request)
    {
        $record = new MapViewDiff();
        $record->setApplicationSlug($element->getApplication()->getSlug());
        $record->setTitle($request->request->get('title'));
        $record->setUserId($request->request->get('savePublic') ? null : $this->getUserId());
        // NOTE: Empty arrays do not survive jQuery Ajax post, will be stripped completely from incoming data
        $record->setViewParams($request->request->get('viewParams'));
        $record->setLayersetDiffs($request->request->get('layersetsDiff', array()));
        $record->setSourceDiffs($request->request->get('sourcesDiff', array()));

        $this->em->persist($record);
        $this->em->flush();

        $config = $element->getConfiguration();
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing-row.html.twig', array(
            'record' => $record,
            'dateFormat' => $this->getDateFormat($request),
            'savePrivate' => $config['privateEntries'] === 'rw',
            'savePublic' => $config['publicEntries'] === 'rw',
        ));
        return new Response($content);
    }

    protected function getDeleteResponse(Entity\Element $element, Request $request)
    {
        $id = $request->query->get('id');
        if (!$id) {
            throw new BadRequestHttpException("Missing id");
        }
        $record = $records = $this->getRepository()->find($id);
        if ($record) {
            $this->em->remove($record);
            $this->em->flush();
        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getDateFormat(Request $request)
    {
        // @todo: locale-dependent format
        return 'Y-m-d'; // . ' H:m:i';
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository */
        $repository = $this->em->getRepository('Mapbender\CoreBundle\Entity\MapViewDiff');
        return $repository;
    }

    protected function isCurrentUserAnonymous()
    {
        $token = $this->tokenStorage->getToken();
        return !$token || ($token instanceof AnonymousToken);
    }

    protected function getUserId()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof AnonymousToken)) {
            return $token->getUser()->getUsername();
        }
        return null;
    }
}
