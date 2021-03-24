<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\MapViewDiff;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        $showPublic = !!$config['publicEntries'];
        $showPrivate = !!$config['privateEntries'];
        $criteria = Criteria::create()->where(Criteria::expr()->eq('applicationSlug', $element->getApplication()->getSlug()));
        $isAnon = $this->isCurrentUserAnonymous();

        if ($showPublic && !$showPrivate) {
            $criteria->andWhere(Criteria::expr()->eq('userId', null));
        } else {
            if ($isAnon) {
                $privateExpression = new CompositeExpression(CompositeExpression::TYPE_AND, array(
                    Criteria::expr()->gt('isAnon', 0),
                    Criteria::expr()->eq('userId', null),
                ));
            } else {
                $privateExpression = Criteria::expr()->eq('userId', $this->getUserId());
            }
            if ($showPrivate && !$showPublic) {
                $criteria->andWhere($privateExpression);
            } else {
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, array(
                    $privateExpression,
                    Criteria::expr()->eq('userId', null),
                )));
            }
        }

        $records = $this->getRepository()->matching($criteria);

        $vars = $this->getGrantsVariables($config) + array(
            'records' => $records,
            'dateFormat' => $this->getDateFormat($request),
        );
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing.html.twig', $vars);
        return new Response($content);
    }

    protected function getSaveResponse(Entity\Element $element, Request $request)
    {
        if ($id = $request->query->get('id')) {
            $record = $this->getRepository()->find($id);
            if (!$record) {
                throw new NotFoundHttpException();
            }
            if ($newTitle = $request->request->get('title')) {
                $record->setTitle($newTitle);
            }
        } else {
            $record = new MapViewDiff();
            $record->setApplicationSlug($element->getApplication()->getSlug());
            $record->setTitle($request->request->get('title'));
        }
        $this->updateRecord($record, $request);

        $this->em->persist($record);
        $this->em->flush();

        $vars = $this->getGrantsVariables($element->getConfiguration()) + array(
            'record' => $record,
            'dateFormat' => $this->getDateFormat($request),
        );
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing-row.html.twig', $vars);
        return new Response($content);
    }

    protected function getDeleteResponse(Entity\Element $element, Request $request)
    {
        $id = $request->query->get('id');
        if (!$id) {
            throw new BadRequestHttpException("Missing id");
        }
        /** @var MapViewDiff|null $record */
        $record = $records = $this->getRepository()->find($id);
        if ($record) {
            if (!$record->getUserId()) {
                if (!$this->isAdmin()) {
                    if (($record->getUserId() !== $this->getUserId() || !$this->checkGrant($element, 'delete'))) {
                        throw new AccessDeniedHttpException();
                    }
                }
            }

            $this->em->remove($record);
            $this->em->flush();
        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    protected function updateRecord(MapviewDiff $record, Request $request)
    {
        $record->setUserId($request->request->get('savePublic') ? null : $this->getUserId());
        $record->setIsAnon($this->isCurrentUserAnonymous());
        // NOTE: Empty arrays do not survive jQuery Ajax post, will be stripped completely from incoming data
        $record->setViewParams($request->request->get('viewParams'));
        $record->setLayersetDiffs($request->request->get('layersetsDiff', array()));
        $record->setSourceDiffs($request->request->get('sourcesDiff', array()));
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

    protected function isAdmin()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof AnonymousToken)) {
            $user = $token->getUser();
            if (\is_object($user) && ($user instanceof User)) {
                return $user->isAdmin();
            }
        }
        return false;
    }

    protected function getGrantsVariables($config)
    {
        return array(
            'savePublic' => $config['publicEntries'] === ViewManager::ACCESS_READWRITE,
            'savePrivate' => !!$config['privateEntries'],
            'allowDelete' => $config['allowNonAdminDelete'] || $this->isAdmin(),
        );
    }

    protected function checkGrant(Entity\Element $element, $operation)
    {
        $grantsVariables = $this->getGrantsVariables($element->getConfiguration());
        switch ($operation) {
            default:
                false;
            case 'delete':
                return $grantsVariables['allowDelete'];
            case 'savePublic':
                return $grantsVariables['savePublic'];
            case 'savePrivate':
                return $grantsVariables['savePrivate'];
        }
    }
}
